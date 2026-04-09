<?php
/**
 * Theme Presets - Temas predefinidos organizados por sector
 *
 * Archivo centralizado con todos los temas disponibles para Flavor Platform.
 * Fácil de extender mediante addons usando el filtro `flavor_theme_presets`.
 *
 * @package FlavorChatIA
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener todos los presets de temas organizados por categoría
 *
 * @return array Array de temas organizados por categoría
 */
function flavor_get_theme_presets() {

    $presets_generales = [
        // ═══════════════════════════════════════════════════════════════════════════
        // CATEGORÍA: General / Base
        // ═══════════════════════════════════════════════════════════════════════════
        'default' => [
            'name' => 'Default',
            'description' => 'Tema por defecto con colores neutros',
            'category' => 'general',
            'category_label' => __('General', 'flavor-platform'),
            'ideal_for' => __('Cualquier proyecto, punto de partida personalizable', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/default.png',
            'font_family_headings' => 'Inter',
            'font_family_body' => 'Inter',
            'variables' => [
                '--flavor-primary' => '#3b82f6',
                '--flavor-primary-hover' => '#2563eb',
                '--flavor-primary-light' => '#dbeafe',
                '--flavor-primary-dark' => '#1d4ed8',
                '--flavor-secondary' => '#6b7280',
                '--flavor-secondary-hover' => '#4b5563',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#f9fafb',
                '--flavor-bg-tertiary' => '#f3f4f6',
                '--flavor-text' => '#1f2937',
                '--flavor-text-secondary' => '#6b7280',
                '--flavor-text-muted' => '#9ca3af',
                '--flavor-border' => '#e5e7eb',
                '--flavor-border-light' => '#f3f4f6',
                '--flavor-success' => '#22c55e',
                '--flavor-warning' => '#f59e0b',
                '--flavor-error' => '#ef4444',
                '--flavor-info' => '#3b82f6',
                '--flavor-shadow-sm' => '0 1px 2px rgba(0,0,0,0.05)',
                '--flavor-shadow' => '0 4px 6px -1px rgba(0,0,0,0.1)',
                '--flavor-shadow-lg' => '0 10px 15px -3px rgba(0,0,0,0.1)',
                '--flavor-radius-sm' => '4px',
                '--flavor-radius' => '8px',
                '--flavor-radius-lg' => '12px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            ],
        ],

        'dark-mode' => [
            'name' => 'Dark Mode',
            'description' => 'Tema oscuro para uso nocturno',
            'category' => 'general',
            'category_label' => __('General', 'flavor-platform'),
            'ideal_for' => __('Apps, plataformas tech, uso nocturno', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/dark.png',
            'font_family_headings' => 'Inter',
            'font_family_body' => 'Inter',
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
                '--flavor-radius-sm' => '4px',
                '--flavor-radius' => '8px',
                '--flavor-radius-lg' => '12px',
                '--flavor-radius-full' => '9999px',
            ],
        ],

        'minimal' => [
            'name' => 'Minimal',
            'description' => 'Tema minimalista en blanco y negro',
            'category' => 'general',
            'category_label' => __('General', 'flavor-platform'),
            'ideal_for' => __('Portfolios, diseño editorial, marcas premium', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/minimal.png',
            'font_family_headings' => 'Inter',
            'font_family_body' => 'Inter',
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
                '--flavor-radius-full' => '9999px',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Salud y Bienestar
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_salud = [
        'salud-vital' => [
            'name' => 'Salud Vital',
            'description' => 'Ideal para clínicas, centros de salud, wellness',
            'category' => 'salud',
            'category_label' => __('Salud y Bienestar', 'flavor-platform'),
            'ideal_for' => __('Clínicas, centros médicos, spas, wellness, farmacias', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/salud-vital.png',
            'font_family_headings' => 'Nunito Sans',
            'font_family_body' => 'Open Sans',
            'variables' => [
                '--flavor-primary' => '#0d9488',
                '--flavor-primary-hover' => '#0f766e',
                '--flavor-primary-light' => '#ccfbf1',
                '--flavor-primary-dark' => '#115e59',
                '--flavor-secondary' => '#14b8a6',
                '--flavor-secondary-hover' => '#0d9488',
                '--flavor-accent' => '#f0fdfa',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#f0fdfa',
                '--flavor-bg-tertiary' => '#ccfbf1',
                '--flavor-text' => '#134e4a',
                '--flavor-text-secondary' => '#0f766e',
                '--flavor-text-muted' => '#5eead4',
                '--flavor-border' => '#99f6e4',
                '--flavor-border-light' => '#ccfbf1',
                '--flavor-success' => '#22c55e',
                '--flavor-warning' => '#f59e0b',
                '--flavor-error' => '#ef4444',
                '--flavor-info' => '#0d9488',
                '--flavor-shadow-sm' => '0 1px 3px rgba(13,148,136,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(13,148,136,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(13,148,136,0.16)',
                '--flavor-radius-sm' => '6px',
                '--flavor-radius' => '10px',
                '--flavor-radius-lg' => '16px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Nunito Sans", "Open Sans", -apple-system, sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Educación
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_educacion = [
        'academia-moderna' => [
            'name' => 'Academia Moderna',
            'description' => 'Para escuelas, academias, formación online',
            'category' => 'educacion',
            'category_label' => __('Educación', 'flavor-platform'),
            'ideal_for' => __('Escuelas, universidades, plataformas e-learning, academias', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/academia-moderna.png',
            'font_family_headings' => 'Poppins',
            'font_family_body' => 'Inter',
            'variables' => [
                '--flavor-primary' => '#7c3aed',
                '--flavor-primary-hover' => '#6d28d9',
                '--flavor-primary-light' => '#ede9fe',
                '--flavor-primary-dark' => '#5b21b6',
                '--flavor-secondary' => '#8b5cf6',
                '--flavor-secondary-hover' => '#7c3aed',
                '--flavor-accent' => '#faf5ff',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#faf5ff',
                '--flavor-bg-tertiary' => '#ede9fe',
                '--flavor-text' => '#1e1b4b',
                '--flavor-text-secondary' => '#5b21b6',
                '--flavor-text-muted' => '#a78bfa',
                '--flavor-border' => '#ddd6fe',
                '--flavor-border-light' => '#ede9fe',
                '--flavor-success' => '#22c55e',
                '--flavor-warning' => '#f59e0b',
                '--flavor-error' => '#ef4444',
                '--flavor-info' => '#7c3aed',
                '--flavor-shadow-sm' => '0 1px 3px rgba(124,58,237,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(124,58,237,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(124,58,237,0.16)',
                '--flavor-radius-sm' => '6px',
                '--flavor-radius' => '12px',
                '--flavor-radius-lg' => '18px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Poppins", "Inter", -apple-system, sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Deportes y Fitness
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_deportes = [
        'fitness-energy' => [
            'name' => 'Fitness Energy',
            'description' => 'Gimnasios, clubs deportivos, entrenadores',
            'category' => 'deportes',
            'category_label' => __('Deportes y Fitness', 'flavor-platform'),
            'ideal_for' => __('Gimnasios, entrenadores personales, clubs deportivos, crossfit', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/fitness-energy.png',
            'font_family_headings' => 'Oswald',
            'font_family_body' => 'Roboto',
            'variables' => [
                '--flavor-primary' => '#dc2626',
                '--flavor-primary-hover' => '#b91c1c',
                '--flavor-primary-light' => '#fef2f2',
                '--flavor-primary-dark' => '#991b1b',
                '--flavor-secondary' => '#f97316',
                '--flavor-secondary-hover' => '#ea580c',
                '--flavor-accent' => '#fef2f2',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#fafafa',
                '--flavor-bg-tertiary' => '#f5f5f5',
                '--flavor-text' => '#171717',
                '--flavor-text-secondary' => '#dc2626',
                '--flavor-text-muted' => '#737373',
                '--flavor-border' => '#e5e5e5',
                '--flavor-border-light' => '#f5f5f5',
                '--flavor-success' => '#22c55e',
                '--flavor-warning' => '#f97316',
                '--flavor-error' => '#dc2626',
                '--flavor-info' => '#3b82f6',
                '--flavor-shadow-sm' => '0 1px 3px rgba(220,38,38,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(220,38,38,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(220,38,38,0.16)',
                '--flavor-radius-sm' => '4px',
                '--flavor-radius' => '6px',
                '--flavor-radius-lg' => '10px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Roboto", "Oswald", -apple-system, sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Cultura y Arte
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_cultura = [
        'galeria-arte' => [
            'name' => 'Galería de Arte',
            'description' => 'Museos, galerías, espacios culturales',
            'category' => 'cultura',
            'category_label' => __('Cultura y Arte', 'flavor-platform'),
            'ideal_for' => __('Museos, galerías de arte, teatros, centros culturales', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/galeria-arte.png',
            'font_family_headings' => 'Playfair Display',
            'font_family_body' => 'Lato',
            'variables' => [
                '--flavor-primary' => '#1f2937',
                '--flavor-primary-hover' => '#111827',
                '--flavor-primary-light' => '#f3f4f6',
                '--flavor-primary-dark' => '#030712',
                '--flavor-secondary' => '#9ca3af',
                '--flavor-secondary-hover' => '#6b7280',
                '--flavor-accent' => '#fafafa',
                '--flavor-bg' => '#fafafa',
                '--flavor-bg-secondary' => '#f5f5f5',
                '--flavor-bg-tertiary' => '#e5e5e5',
                '--flavor-text' => '#1f2937',
                '--flavor-text-secondary' => '#4b5563',
                '--flavor-text-muted' => '#9ca3af',
                '--flavor-border' => '#d1d5db',
                '--flavor-border-light' => '#e5e7eb',
                '--flavor-success' => '#059669',
                '--flavor-warning' => '#d97706',
                '--flavor-error' => '#dc2626',
                '--flavor-info' => '#2563eb',
                '--flavor-shadow-sm' => '0 1px 2px rgba(0,0,0,0.04)',
                '--flavor-shadow' => '0 2px 4px rgba(0,0,0,0.06)',
                '--flavor-shadow-lg' => '0 4px 12px rgba(0,0,0,0.08)',
                '--flavor-radius-sm' => '0px',
                '--flavor-radius' => '0px',
                '--flavor-radius-lg' => '0px',
                '--flavor-radius-full' => '0px',
                '--flavor-font-family' => '"Lato", "Playfair Display", Georgia, serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Tecnología
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_tecnologia = [
        'tech-startup' => [
            'name' => 'Tech Startup',
            'description' => 'Startups, empresas tech, SaaS',
            'category' => 'tecnologia',
            'category_label' => __('Tecnología', 'flavor-platform'),
            'ideal_for' => __('Startups, apps SaaS, empresas de software, agencias digitales', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/tech-startup.png',
            'font_family_headings' => 'Space Grotesk',
            'font_family_body' => 'Inter',
            'variables' => [
                '--flavor-primary' => '#6366f1',
                '--flavor-primary-hover' => '#4f46e5',
                '--flavor-primary-light' => '#eef2ff',
                '--flavor-primary-dark' => '#4338ca',
                '--flavor-secondary' => '#8b5cf6',
                '--flavor-secondary-hover' => '#7c3aed',
                '--flavor-accent' => '#eef2ff',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#f8fafc',
                '--flavor-bg-tertiary' => '#f1f5f9',
                '--flavor-text' => '#0f172a',
                '--flavor-text-secondary' => '#475569',
                '--flavor-text-muted' => '#94a3b8',
                '--flavor-border' => '#e2e8f0',
                '--flavor-border-light' => '#f1f5f9',
                '--flavor-success' => '#22c55e',
                '--flavor-warning' => '#f59e0b',
                '--flavor-error' => '#ef4444',
                '--flavor-info' => '#6366f1',
                '--flavor-shadow-sm' => '0 1px 3px rgba(99,102,241,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(99,102,241,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(99,102,241,0.16)',
                '--flavor-radius-sm' => '6px',
                '--flavor-radius' => '12px',
                '--flavor-radius-lg' => '16px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Inter", "Space Grotesk", -apple-system, sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Alimentación / Eco
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_alimentacion = [
        'organic-fresh' => [
            'name' => 'Organic Fresh',
            'description' => 'Tiendas eco, mercados orgánicos',
            'category' => 'alimentacion',
            'category_label' => __('Alimentación', 'flavor-platform'),
            'ideal_for' => __('Tiendas eco, mercados orgánicos, granjas, huertos urbanos', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/organic-fresh.png',
            'font_family_headings' => 'Nunito',
            'font_family_body' => 'Open Sans',
            'variables' => [
                '--flavor-primary' => '#65a30d',
                '--flavor-primary-hover' => '#4d7c0f',
                '--flavor-primary-light' => '#ecfccb',
                '--flavor-primary-dark' => '#3f6212',
                '--flavor-secondary' => '#84cc16',
                '--flavor-secondary-hover' => '#65a30d',
                '--flavor-accent' => '#f7fee7',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#f7fee7',
                '--flavor-bg-tertiary' => '#ecfccb',
                '--flavor-text' => '#1a2e05',
                '--flavor-text-secondary' => '#4d7c0f',
                '--flavor-text-muted' => '#84cc16',
                '--flavor-border' => '#bef264',
                '--flavor-border-light' => '#d9f99d',
                '--flavor-success' => '#65a30d',
                '--flavor-warning' => '#eab308',
                '--flavor-error' => '#dc2626',
                '--flavor-info' => '#0891b2',
                '--flavor-shadow-sm' => '0 1px 3px rgba(101,163,13,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(101,163,13,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(101,163,13,0.16)',
                '--flavor-radius-sm' => '8px',
                '--flavor-radius' => '14px',
                '--flavor-radius-lg' => '20px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Nunito", "Open Sans", -apple-system, sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Inmobiliaria
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_inmobiliaria = [
        'real-estate-pro' => [
            'name' => 'Real Estate Pro',
            'description' => 'Inmobiliarias, arquitectos, constructoras',
            'category' => 'inmobiliaria',
            'category_label' => __('Inmobiliaria', 'flavor-platform'),
            'ideal_for' => __('Inmobiliarias, agencias, arquitectos, constructoras, interiorismo', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/real-estate-pro.png',
            'font_family_headings' => 'Montserrat',
            'font_family_body' => 'Source Sans Pro',
            'variables' => [
                '--flavor-primary' => '#0369a1',
                '--flavor-primary-hover' => '#075985',
                '--flavor-primary-light' => '#e0f2fe',
                '--flavor-primary-dark' => '#0c4a6e',
                '--flavor-secondary' => '#0284c7',
                '--flavor-secondary-hover' => '#0369a1',
                '--flavor-accent' => '#f0f9ff',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#f8fafc',
                '--flavor-bg-tertiary' => '#f1f5f9',
                '--flavor-text' => '#0f172a',
                '--flavor-text-secondary' => '#0c4a6e',
                '--flavor-text-muted' => '#64748b',
                '--flavor-border' => '#cbd5e1',
                '--flavor-border-light' => '#e2e8f0',
                '--flavor-success' => '#16a34a',
                '--flavor-warning' => '#d97706',
                '--flavor-error' => '#dc2626',
                '--flavor-info' => '#0369a1',
                '--flavor-shadow-sm' => '0 1px 3px rgba(3,105,161,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(3,105,161,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(3,105,161,0.16)',
                '--flavor-radius-sm' => '4px',
                '--flavor-radius' => '8px',
                '--flavor-radius-lg' => '12px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Source Sans Pro", "Montserrat", -apple-system, sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Legal y Finanzas
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_legal = [
        'corporate-trust' => [
            'name' => 'Corporate Trust',
            'description' => 'Abogados, consultoras, finanzas',
            'category' => 'legal',
            'category_label' => __('Legal y Finanzas', 'flavor-platform'),
            'ideal_for' => __('Bufetes de abogados, consultoras, asesores financieros, bancos', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/corporate-trust.png',
            'font_family_headings' => 'Merriweather',
            'font_family_body' => 'Open Sans',
            'variables' => [
                '--flavor-primary' => '#1e3a5f',
                '--flavor-primary-hover' => '#172e4d',
                '--flavor-primary-light' => '#e0e7ef',
                '--flavor-primary-dark' => '#0f1f33',
                '--flavor-secondary' => '#2563eb',
                '--flavor-secondary-hover' => '#1d4ed8',
                '--flavor-accent' => '#f0f4f8',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#f8fafc',
                '--flavor-bg-tertiary' => '#f1f5f9',
                '--flavor-text' => '#0f172a',
                '--flavor-text-secondary' => '#1e3a5f',
                '--flavor-text-muted' => '#64748b',
                '--flavor-border' => '#cbd5e1',
                '--flavor-border-light' => '#e2e8f0',
                '--flavor-success' => '#059669',
                '--flavor-warning' => '#d97706',
                '--flavor-error' => '#dc2626',
                '--flavor-info' => '#2563eb',
                '--flavor-shadow-sm' => '0 1px 2px rgba(30,58,95,0.04)',
                '--flavor-shadow' => '0 2px 4px rgba(30,58,95,0.06)',
                '--flavor-shadow-lg' => '0 4px 12px rgba(30,58,95,0.10)',
                '--flavor-radius-sm' => '3px',
                '--flavor-radius' => '6px',
                '--flavor-radius-lg' => '8px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Open Sans", "Merriweather", Georgia, serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Hostelería
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_hosteleria = [
        'gastro-deluxe' => [
            'name' => 'Gastro Deluxe',
            'description' => 'Restaurantes gourmet, hoteles boutique',
            'category' => 'hosteleria',
            'category_label' => __('Hostelería', 'flavor-platform'),
            'ideal_for' => __('Restaurantes gourmet, hoteles boutique, bodegas, catering', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/gastro-deluxe.png',
            'font_family_headings' => 'Cormorant Garamond',
            'font_family_body' => 'Lato',
            'variables' => [
                '--flavor-primary' => '#92400e',
                '--flavor-primary-hover' => '#78350f',
                '--flavor-primary-light' => '#fef3c7',
                '--flavor-primary-dark' => '#451a03',
                '--flavor-secondary' => '#d97706',
                '--flavor-secondary-hover' => '#b45309',
                '--flavor-accent' => '#fffbeb',
                '--flavor-bg' => '#fffbeb',
                '--flavor-bg-secondary' => '#fef3c7',
                '--flavor-bg-tertiary' => '#fde68a',
                '--flavor-text' => '#451a03',
                '--flavor-text-secondary' => '#92400e',
                '--flavor-text-muted' => '#b45309',
                '--flavor-border' => '#fcd34d',
                '--flavor-border-light' => '#fde68a',
                '--flavor-success' => '#16a34a',
                '--flavor-warning' => '#d97706',
                '--flavor-error' => '#dc2626',
                '--flavor-info' => '#0891b2',
                '--flavor-shadow-sm' => '0 1px 3px rgba(146,64,14,0.08)',
                '--flavor-shadow' => '0 4px 6px rgba(146,64,14,0.12)',
                '--flavor-shadow-lg' => '0 10px 25px rgba(146,64,14,0.16)',
                '--flavor-radius-sm' => '4px',
                '--flavor-radius' => '8px',
                '--flavor-radius-lg' => '12px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Lato", "Cormorant Garamond", Georgia, serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // CATEGORÍA: Infantil
    // ═══════════════════════════════════════════════════════════════════════════
    $presets_infantil = [
        'kids-fun' => [
            'name' => 'Kids Fun',
            'description' => 'Guarderías, actividades infantiles, jugueterías',
            'category' => 'infantil',
            'category_label' => __('Infantil', 'flavor-platform'),
            'ideal_for' => __('Guarderías, ludotecas, jugueterías, actividades para niños', 'flavor-platform'),
            'preview' => FLAVOR_CHAT_IA_URL . 'assets/images/themes/kids-fun.png',
            'font_family_headings' => 'Quicksand',
            'font_family_body' => 'Nunito',
            'variables' => [
                '--flavor-primary' => '#f97316',
                '--flavor-primary-hover' => '#ea580c',
                '--flavor-primary-light' => '#fff7ed',
                '--flavor-primary-dark' => '#c2410c',
                '--flavor-secondary' => '#facc15',
                '--flavor-secondary-hover' => '#eab308',
                '--flavor-accent' => '#a855f7',
                '--flavor-tertiary' => '#a855f7',
                '--flavor-bg' => '#ffffff',
                '--flavor-bg-secondary' => '#fffbeb',
                '--flavor-bg-tertiary' => '#fef3c7',
                '--flavor-text' => '#1c1917',
                '--flavor-text-secondary' => '#f97316',
                '--flavor-text-muted' => '#78716c',
                '--flavor-border' => '#fed7aa',
                '--flavor-border-light' => '#ffedd5',
                '--flavor-success' => '#22c55e',
                '--flavor-warning' => '#facc15',
                '--flavor-error' => '#f87171',
                '--flavor-info' => '#38bdf8',
                '--flavor-shadow-sm' => '0 1px 3px rgba(249,115,22,0.1)',
                '--flavor-shadow' => '0 4px 8px rgba(249,115,22,0.15)',
                '--flavor-shadow-lg' => '0 12px 30px rgba(249,115,22,0.2)',
                '--flavor-radius-sm' => '10px',
                '--flavor-radius' => '18px',
                '--flavor-radius-lg' => '26px',
                '--flavor-radius-full' => '9999px',
                '--flavor-font-family' => '"Nunito", "Quicksand", "Comic Neue", sans-serif',
            ],
        ],
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // Combinar todos los presets
    // ═══════════════════════════════════════════════════════════════════════════
    $todos_los_presets = array_merge(
        $presets_generales,
        $presets_salud,
        $presets_educacion,
        $presets_deportes,
        $presets_cultura,
        $presets_tecnologia,
        $presets_alimentacion,
        $presets_inmobiliaria,
        $presets_legal,
        $presets_hosteleria,
        $presets_infantil
    );

    /**
     * Filtro para extender los presets de temas
     *
     * Permite a addons y plugins de terceros añadir sus propios temas.
     *
     * @param array $todos_los_presets Array con todos los temas predefinidos
     */
    return apply_filters('flavor_theme_presets', $todos_los_presets);
}

/**
 * Obtener categorías de temas disponibles
 *
 * @return array Array de categorías con su etiqueta traducida
 */
function flavor_get_theme_categories() {
    $categorias = [
        'all' => __('Todos los temas', 'flavor-platform'),
        'general' => __('General', 'flavor-platform'),
        'salud' => __('Salud y Bienestar', 'flavor-platform'),
        'educacion' => __('Educación', 'flavor-platform'),
        'deportes' => __('Deportes y Fitness', 'flavor-platform'),
        'cultura' => __('Cultura y Arte', 'flavor-platform'),
        'tecnologia' => __('Tecnología', 'flavor-platform'),
        'alimentacion' => __('Alimentación', 'flavor-platform'),
        'inmobiliaria' => __('Inmobiliaria', 'flavor-platform'),
        'legal' => __('Legal y Finanzas', 'flavor-platform'),
        'hosteleria' => __('Hostelería', 'flavor-platform'),
        'infantil' => __('Infantil', 'flavor-platform'),
    ];

    /**
     * Filtro para extender las categorías de temas
     *
     * @param array $categorias Array de categorías
     */
    return apply_filters('flavor_theme_categories', $categorias);
}

/**
 * Obtener temas filtrados por categoría
 *
 * @param string $categoria ID de la categoría (o 'all' para todos)
 * @return array Array de temas filtrados
 */
function flavor_get_themes_by_category($categoria = 'all') {
    $todos_los_temas = flavor_get_theme_presets();

    if ($categoria === 'all') {
        return $todos_los_temas;
    }

    $temas_filtrados = [];
    foreach ($todos_los_temas as $identificador_tema => $configuracion_tema) {
        if (isset($configuracion_tema['category']) && $configuracion_tema['category'] === $categoria) {
            $temas_filtrados[$identificador_tema] = $configuracion_tema;
        }
    }

    return $temas_filtrados;
}
