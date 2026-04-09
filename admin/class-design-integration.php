<?php
/**
 * Design Settings Integration
 *
 * Integra design-settings existente con theme-customizer nuevo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Design_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Añadir tab de Theme Customizer a la página de diseño
        add_action('flavor_design_settings_tabs', [$this, 'add_theme_tab']);
        add_action('flavor_design_settings_content', [$this, 'render_theme_content']);
    }

    /**
     * Añade tab de personalización de tema
     */
    public function add_theme_tab() {
        ?>
        <a href="#theme-customizer" class="nav-tab">
            <?php _e('Tema & Dark Mode', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="flavor-badge flavor-badge--new">NUEVO</span>
        </a>
        <?php
    }

    /**
     * Renderiza contenido del tab
     */
    public function render_theme_content() {
        ?>
        <div id="theme-customizer" class="flavor-tab-panel">
            <h2><?php _e('Personalización de Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-info-box">
                <h3>🎨 <?php _e('Nueva Funcionalidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('El sistema de personalización de tema permite a los usuarios finales personalizar colores y activar dark mode desde el frontend.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <ul>
                    <li>✅ <strong>Dark Mode</strong> - Toggle flotante para usuarios</li>
                    <li>✅ <strong>Personalización de Colores</strong> - Color pickers frontend</li>
                    <li>✅ <strong>Presets</strong> - 5 combinaciones predefinidas</li>
                    <li>✅ <strong>Por Usuario</strong> - Cada usuario guarda sus preferencias</li>
                </ul>
            </div>

            <div class="flavor-grid-2">
                <div class="flavor-card">
                    <h3><?php _e('Dark Mode Global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('El botón de dark mode está activo automáticamente en todas las páginas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p><strong><?php _e('Ubicación:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> Botón flotante esquina inferior derecha</p>
                    <p><strong><?php _e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <span style="color: #10b981;">✓ Activo</span></p>
                </div>

                <div class="flavor-card">
                    <h3><?php _e('Página de Personalización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Crea una página con el shortcode para permitir personalización completa:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <code style="display: block; background: #f3f4f6; padding: 12px; border-radius: 6px; margin: 10px 0;">
                        [flavor_theme_customizer]
                    </code>
                    <p><small><?php _e('Ejemplo: /configuracion/ o /mi-cuenta/personalizacion/', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small></p>
                </div>
            </div>

            <h3><?php _e('Colores Predeterminados del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Estos son los colores base que se usan si el usuario no personaliza:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <?php
            $default_colors = [
                'primary' => '#3b82f6',
                'secondary' => '#8b5cf6',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'danger' => '#ef4444',
            ];

            echo '<div class="flavor-color-grid">';
            foreach ($default_colors as $name => $color) {
                echo sprintf(
                    '<div class="flavor-color-preview">
                        <div class="flavor-color-swatch" style="background: %s;"></div>
                        <div class="flavor-color-info">
                            <strong>%s</strong><br>
                            <code>%s</code>
                        </div>
                    </div>',
                    esc_attr($color),
                    esc_html(ucfirst($name)),
                    esc_html($color)
                );
            }
            echo '</div>';
            ?>

            <h3><?php _e('Presets Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Los usuarios pueden elegir entre estos presets predefinidos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <?php
            $presets = [
                'default' => ['name' => 'Por Defecto', 'primary' => '#3b82f6', 'secondary' => '#8b5cf6'],
                'ocean' => ['name' => 'Océano', 'primary' => '#0ea5e9', 'secondary' => '#06b6d4'],
                'forest' => ['name' => 'Bosque', 'primary' => '#10b981', 'secondary' => '#059669'],
                'sunset' => ['name' => 'Atardecer', 'primary' => '#f59e0b', 'secondary' => '#ef4444'],
                'purple' => ['name' => 'Púrpura', 'primary' => '#8b5cf6', 'secondary' => '#a855f7'],
            ];

            echo '<div class="flavor-presets-showcase">';
            foreach ($presets as $key => $preset) {
                echo sprintf(
                    '<div class="flavor-preset-card">
                        <div class="flavor-preset-gradient" style="background: linear-gradient(135deg, %s 0%%, %s 100%%);"></div>
                        <div class="flavor-preset-name">%s</div>
                    </div>',
                    esc_attr($preset['primary']),
                    esc_attr($preset['secondary']),
                    esc_html($preset['name'])
                );
            }
            echo '</div>';
            ?>

            <div class="flavor-info-box flavor-info-box--tip">
                <h4>💡 <?php _e('Tip:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Los colores configurados en "Colores Principales" arriba se usan como CSS variables globales. Los usuarios pueden sobreescribirlos con sus preferencias personales.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p><?php _e('Las variables CSS generadas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <code style="display: block; white-space: pre; background: #1f2937; color: #f9fafb; padding: 12px; border-radius: 6px; font-size: 12px;">
:root {
  --flavor-primary: #3b82f6;
  --flavor-secondary: #8b5cf6;
  --flavor-success: #10b981;
  --flavor-warning: #f59e0b;
  --flavor-danger: #ef4444;
}</code>
            </div>

            <style>
            .flavor-grid-2 {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin: 20px 0;
            }
            .flavor-card {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px;
            }
            .flavor-card h3 {
                margin-top: 0;
            }
            .flavor-info-box {
                background: #eff6ff;
                border-left: 4px solid #3b82f6;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
            }
            .flavor-info-box--tip {
                background: #fef3c7;
                border-left-color: #f59e0b;
            }
            .flavor-info-box h3,
            .flavor-info-box h4 {
                margin-top: 0;
            }
            .flavor-info-box ul {
                margin: 10px 0;
            }
            .flavor-color-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 16px;
                margin: 20px 0;
            }
            .flavor-color-preview {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 12px;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
            }
            .flavor-color-swatch {
                height: 60px;
                border-radius: 6px;
                border: 1px solid #e5e7eb;
            }
            .flavor-color-info {
                font-size: 13px;
            }
            .flavor-presets-showcase {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 16px;
                margin: 20px 0;
            }
            .flavor-preset-card {
                border-radius: 12px;
                overflow: hidden;
                border: 2px solid #e5e7eb;
                transition: all 0.2s;
            }
            .flavor-preset-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            .flavor-preset-gradient {
                height: 80px;
            }
            .flavor-preset-name {
                padding: 10px;
                text-align: center;
                background: white;
                font-weight: 500;
                font-size: 13px;
            }
            .flavor-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 600;
                margin-left: 6px;
            }
            .flavor-badge--new {
                background: #10b981;
                color: white;
            }
            </style>
        </div>
        <?php
    }
}

// Inicializar si estamos en admin
if (is_admin()) {
    Flavor_Design_Integration::get_instance();
}
