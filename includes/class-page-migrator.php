<?php
/**
 * Page Migrator - Actualiza páginas existentes al nuevo formato
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

class Flavor_Page_Migrator {

    /**
     * Migra todas las páginas auto-creadas al nuevo formato
     */
    public static function migrate_all_pages() {
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_flavor_auto_page',
                    'value' => '1',
                ],
            ],
        ]);

        $migrated = [];
        $skipped = [];

        foreach ($pages as $page) {
            $result = self::migrate_single_page($page->ID);
            if ($result['migrated']) {
                $migrated[] = $page->post_title;
            } else {
                $skipped[] = $page->post_title;
            }
        }

        return [
            'migrated' => $migrated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Migra una página individual
     */
    public static function migrate_single_page($page_id) {
        $page = get_post($page_id);
        if (!$page) {
            return ['migrated' => false, 'reason' => 'Page not found'];
        }

        $content = $page->post_content;

        // Si ya tiene flavor_page_header, skip
        if (strpos($content, '[flavor_page_header') !== false) {
            return ['migrated' => false, 'reason' => 'Already migrated'];
        }

        // Extraer información del contenido antiguo
        $title = '';
        $subtitle = '';
        $module = '';
        $remaining_content = $content;

        // Extraer <h1>
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
            $title = strip_tags($matches[1]);
            $remaining_content = preg_replace('/<h1[^>]*>.*?<\/h1>/is', '', $remaining_content);
        }

        // Extraer <p> inmediatamente después del h1
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $remaining_content, $matches)) {
            $subtitle = strip_tags($matches[1]);
            $remaining_content = preg_replace('/<p[^>]*>.*?<\/p>/is', '', $remaining_content, 1);
        }

        // Detectar módulo del shortcode
        if (preg_match('/module=["\']([^"\']+)["\']/', $content, $matches)) {
            $module = $matches[1];
        }

        // Si no pudimos extraer título, usar el título de la página
        if (empty($title)) {
            $title = $page->post_title;
        }

        // Determinar current desde el slug
        $current = self::determine_current_from_slug($page->post_name);

        // Determinar background (gradient para principales, white para resto)
        $background = ($page->post_parent == 0) ? 'gradient' : 'white';

        // Construir nuevo contenido
        $new_content = sprintf(
            '[flavor_page_header
    title="%s"%s
    breadcrumbs="yes"
    background="%s"%s%s]',
            esc_attr($title),
            !empty($subtitle) ? "\n    subtitle=\"" . esc_attr($subtitle) . "\"" : '',
            $background,
            !empty($module) ? "\n    module=\"{$module}\"" : '',
            !empty($current) ? "\n    current=\"{$current}\"" : ''
        );

        // Añadir contenido restante (limpiando espacios extra)
        $remaining_content = trim($remaining_content);
        if (!empty($remaining_content)) {
            $new_content .= "\n\n" . $remaining_content;
        }

        // Actualizar página
        wp_update_post([
            'ID' => $page_id,
            'post_content' => $new_content,
        ]);

        // Asegurar metas
        update_post_meta($page_id, '_flavor_full_width', 1);
        self::assign_full_width_template($page_id);

        return ['migrated' => true, 'new_content' => $new_content];
    }

    /**
     * Determina el valor de 'current' desde el slug
     */
    private static function determine_current_from_slug($slug) {
        $mapping = [
            'crear' => 'crear',
            'nuevo-tema' => 'nuevo-tema',
            'inscribirse' => 'inscribirse',
            'mis-talleres' => 'mis-talleres',
            'mis-eventos' => 'mis-eventos',
            'mis-incidencias' => 'mis-incidencias',
            'mis-reservas' => 'mis-reservas',
            'mis-facturas' => 'mis-facturas',
            'mi-grupo' => 'mi-grupo',
            'mi-perfil' => 'mi-perfil',
            'pedidos' => 'pedidos',
            'buscar' => 'buscar',
            'unirme' => 'unirme',
            'pagar-cuota' => 'pagar-cuota',
            'actualizar-datos' => 'actualizar-datos',
            'entrada' => 'entrada',
            'salida' => 'salida',
            'pausar' => 'pausar',
            'reanudar' => 'reanudar',
            'solicitar-correccion' => 'solicitar-correccion',
            'swap' => 'swap',
            'liquidez' => 'liquidez',
            'portfolio' => 'portfolio',
            'comprar' => 'comprar',
            'vender' => 'vender',
            'reservar' => 'reservar',
        ];

        // Si es una página principal, usar 'listado'
        if (in_array($slug, ['talleres', 'eventos', 'incidencias', 'espacios-comunes', 'grupos-consumo', 'facturas', 'foros', 'dex-solana', 'trading-ia', 'socios', 'fichaje'])) {
            return 'listado';
        }

        return $mapping[$slug] ?? '';
    }

    /**
     * Asigna template full-width
     */
    private static function assign_full_width_template($page_id) {
        $templates = ['template-full-width.php', 'full-width.php', 'page-templates/full-width.php'];
        foreach ($templates as $template) {
            $template_file = get_template_directory() . '/' . $template;
            if (file_exists($template_file)) {
                update_post_meta($page_id, '_wp_page_template', $template);
                return true;
            }
        }
        return false;
    }

    /**
     * Crea comando WP-CLI para ejecutar migración
     */
    public static function register_cli_command() {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('flavor migrate-pages', [__CLASS__, 'cli_migrate_pages']);
        }
    }

    /**
     * Comando CLI para migrar páginas
     */
    public static function cli_migrate_pages($args, $assoc_args) {
        WP_CLI::line('Starting page migration...');

        $result = self::migrate_all_pages();

        WP_CLI::success(sprintf(
            'Migration completed! Migrated: %d, Skipped: %d',
            count($result['migrated']),
            count($result['skipped'])
        ));

        if (!empty($result['migrated'])) {
            WP_CLI::line('Migrated pages:');
            foreach ($result['migrated'] as $title) {
                WP_CLI::line(" - {$title}");
            }
        }

        if (!empty($result['skipped'])) {
            WP_CLI::line('Skipped pages:');
            foreach ($result['skipped'] as $title) {
                WP_CLI::line(" - {$title}");
            }
        }
    }
}

// Registrar comando CLI
Flavor_Page_Migrator::register_cli_command();
