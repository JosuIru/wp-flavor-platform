<?php
/**
 * Generador Automático de Documentación de Módulos
 *
 * Analiza el código de cada módulo y genera documentación Markdown.
 *
 * Ejecutar: wp eval-file docs/generar-documentacion.php
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    $wp_load_paths = [
        dirname(__FILE__) . '/../../../../../wp-load.php',
        dirname(__FILE__) . '/../../../../wp-load.php',
    ];
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

class Flavor_Doc_Generator {

    private $modules_dir;
    private $docs_dir;
    private $templates_dir;

    public function __construct() {
        $this->modules_dir = FLAVOR_CHAT_IA_PATH . 'includes/modules/';
        $this->docs_dir = FLAVOR_CHAT_IA_PATH . 'docs/modulos/';
        $this->templates_dir = FLAVOR_CHAT_IA_PATH . 'templates/';

        if (!is_dir($this->docs_dir)) {
            mkdir($this->docs_dir, 0755, true);
        }
    }

    public function generate_all() {
        $modules = glob($this->modules_dir . '*', GLOB_ONLYDIR);
        $generated = 0;
        $skipped = 0;

        foreach ($modules as $module_path) {
            $module_slug = basename($module_path);

            // Saltar si ya existe documentación manual
            $doc_file = $this->docs_dir . $module_slug . '.md';
            if (file_exists($doc_file) && filesize($doc_file) > 5000) {
                echo "Saltando {$module_slug} (documentación manual existente)\n";
                $skipped++;
                continue;
            }

            $doc = $this->generate_module_doc($module_slug, $module_path);
            if ($doc) {
                file_put_contents($doc_file, $doc);
                echo "Generado: {$module_slug}.md\n";
                $generated++;
            }
        }

        echo "\n=== Resumen ===\n";
        echo "Generados: {$generated}\n";
        echo "Saltados: {$skipped}\n";
    }

    public function generate_module_doc($slug, $path) {
        $class_file = $path . '/class-' . $slug . '-module.php';

        if (!file_exists($class_file)) {
            return null;
        }

        $class_content = file_get_contents($class_file);
        $info = $this->extract_module_info($slug, $path, $class_content);

        return $this->render_doc($info);
    }

    private function extract_module_info($slug, $path, $class_content) {
        $info = [
            'slug' => $slug,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'description' => $this->extract_description($class_content),
            'files' => $this->list_files($path),
            'cpts' => $this->extract_cpts($class_content),
            'taxonomies' => $this->extract_taxonomies($class_content),
            'tables' => $this->extract_tables($path),
            'shortcodes' => $this->extract_shortcodes($class_content),
            'dashboard_tab' => $this->check_dashboard_tab($path, $slug),
            'widget' => $this->check_widget($path, $slug),
            'pages' => $this->extract_pages($class_content),
            'views' => $this->list_views($path),
            'hooks' => $this->extract_hooks($class_content),
        ];

        return $info;
    }

    private function extract_description($content) {
        if (preg_match('/\*\s+(?:Módulo|Module|Sistema|Gestión)\s+(?:de\s+)?(.+?)\s*\n/i', $content, $match)) {
            return trim($match[1]);
        }
        return 'Módulo del sistema';
    }

    private function list_files($path) {
        $files = [];
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($items as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $relative = str_replace($path . '/', '', $item->getPathname());
                $files[] = $relative;
            }
        }

        return $files;
    }

    private function extract_cpts($content) {
        $cpts = [];
        preg_match_all("/register_post_type\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $cpt) {
                $cpts[] = $cpt;
            }
        }

        // Buscar también en constantes o propiedades
        preg_match_all("/post_type['\"\s]*(?:=>|=)\s*['\"]([^'\"]+)['\"]/i", $content, $matches2);
        if (!empty($matches2[1])) {
            foreach ($matches2[1] as $cpt) {
                if (!in_array($cpt, $cpts) && strpos($cpt, 'fc_') === 0) {
                    $cpts[] = $cpt;
                }
            }
        }

        return array_unique($cpts);
    }

    private function extract_taxonomies($content) {
        $taxonomies = [];
        preg_match_all("/register_taxonomy\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $tax) {
                $taxonomies[] = $tax;
            }
        }
        return array_unique($taxonomies);
    }

    private function extract_tables($path) {
        $install_file = $path . '/install.php';
        $tables = [];

        if (!file_exists($install_file)) {
            return $tables;
        }

        $content = file_get_contents($install_file);

        // Buscar CREATE TABLE
        preg_match_all("/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:\{\\\$[^}]+\})?([a-z_]+)\s*\(/i", $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $table) {
                $table_name = preg_replace('/^(wp_)?flavor_/', '', $table);
                $tables[] = $table_name;
            }
        }

        // Buscar también en variables
        preg_match_all("/\\\$tabla[s]?\s*=\s*['\"]([^'\"]+)['\"]/", $content, $matches2);
        if (!empty($matches2[1])) {
            foreach ($matches2[1] as $table) {
                $tables[] = str_replace(['$wpdb->prefix', 'flavor_'], '', $table);
            }
        }

        return array_unique($tables);
    }

    private function extract_shortcodes($content) {
        $shortcodes = [];
        preg_match_all("/add_shortcode\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $shortcode) {
                $shortcodes[] = $shortcode;
            }
        }

        return array_unique($shortcodes);
    }

    private function check_dashboard_tab($path, $slug) {
        $patterns = [
            $path . '/class-' . $slug . '-dashboard-tab.php',
            $path . '/class-' . str_replace('-', '_', $slug) . '-dashboard-tab.php',
        ];

        foreach ($patterns as $file) {
            if (file_exists($file)) {
                return basename($file);
            }
        }

        // Buscar en frontend controller
        $frontend = $path . '/frontend/class-' . $slug . '-frontend-controller.php';
        if (file_exists($frontend)) {
            $content = file_get_contents($frontend);
            if (strpos($content, 'registrar_tabs') !== false) {
                return 'Frontend Controller';
            }
        }

        return null;
    }

    private function check_widget($path, $slug) {
        $patterns = [
            $path . '/class-' . $slug . '-widget.php',
            $path . '/class-' . $slug . '-dashboard-widget.php',
        ];

        foreach ($patterns as $file) {
            if (file_exists($file)) {
                return basename($file);
            }
        }

        return null;
    }

    private function extract_pages($content) {
        $pages = [];

        // Buscar en get_pages_definition
        if (preg_match("/function\s+get_pages_definition\s*\([^)]*\)\s*\{(.*?)\n\s{4}\}/s", $content, $match)) {
            $def_content = $match[1];

            // Extraer acciones/páginas
            preg_match_all("/['\"]([a-z_-]+)['\"]\s*=>\s*\[/", $def_content, $actions);
            if (!empty($actions[1])) {
                $pages = $actions[1];
            }
        }

        // Buscar en get_renderer_config tabs
        if (preg_match("/['\"](tabs)['\"]\s*=>\s*\[(.*?)\],\s*\]/s", $content, $match)) {
            preg_match_all("/['\"]([a-z_-]+)['\"]\s*=>\s*\[/", $match[2], $tabs);
            if (!empty($tabs[1])) {
                $pages = array_merge($pages, $tabs[1]);
            }
        }

        return array_unique($pages);
    }

    private function list_views($path) {
        $views_dir = $path . '/views/';
        $views = [];

        if (is_dir($views_dir)) {
            $files = glob($views_dir . '*.php');
            foreach ($files as $file) {
                $views[] = basename($file, '.php');
            }
        }

        return $views;
    }

    private function extract_hooks($content) {
        $hooks = [
            'actions' => [],
            'filters' => [],
        ];

        // do_action
        preg_match_all("/do_action\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $actions);
        if (!empty($actions[1])) {
            $hooks['actions'] = array_unique($actions[1]);
        }

        // apply_filters
        preg_match_all("/apply_filters\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $filters);
        if (!empty($filters[1])) {
            $hooks['filters'] = array_unique($filters[1]);
        }

        return $hooks;
    }

    private function render_doc($info) {
        $doc = "# Módulo: {$info['name']}\n\n";
        $doc .= "> {$info['description']}\n\n";

        // Archivos
        $doc .= "## Archivos Principales\n\n";
        $doc .= "```\nincludes/modules/{$info['slug']}/\n";
        foreach ($info['files'] as $file) {
            $doc .= "├── {$file}\n";
        }
        $doc .= "```\n\n";

        // CPTs
        if (!empty($info['cpts'])) {
            $doc .= "## CPTs (Custom Post Types)\n\n";
            $doc .= "| CPT | Slug |\n";
            $doc .= "|-----|------|\n";
            foreach ($info['cpts'] as $cpt) {
                $name = ucwords(str_replace(['fc_', '_'], ['', ' '], $cpt));
                $doc .= "| {$name} | `{$cpt}` |\n";
            }
            $doc .= "\n";
        }

        // Taxonomías
        if (!empty($info['taxonomies'])) {
            $doc .= "## Taxonomías\n\n";
            $doc .= "| Taxonomía | Slug |\n";
            $doc .= "|-----------|------|\n";
            foreach ($info['taxonomies'] as $tax) {
                $name = ucwords(str_replace('_', ' ', $tax));
                $doc .= "| {$name} | `{$tax}` |\n";
            }
            $doc .= "\n";
        }

        // Tablas BD
        if (!empty($info['tables'])) {
            $doc .= "## Tablas de Base de Datos\n\n";
            foreach ($info['tables'] as $table) {
                $doc .= "- `wp_flavor_{$table}`\n";
            }
            $doc .= "\n";
        }

        // Shortcodes
        if (!empty($info['shortcodes'])) {
            $doc .= "## Shortcodes\n\n";
            foreach ($info['shortcodes'] as $sc) {
                $doc .= "- `[{$sc}]`\n";
            }
            $doc .= "\n";
        }

        // Dashboard Tab
        $doc .= "## Dashboard Tab\n\n";
        if ($info['dashboard_tab']) {
            $doc .= "**Archivo:** `{$info['dashboard_tab']}`\n\n";
        } else {
            $doc .= "No implementado\n\n";
        }

        // Widget
        $doc .= "## Widget Dashboard\n\n";
        if ($info['widget']) {
            $doc .= "**Archivo:** `{$info['widget']}`\n\n";
        } else {
            $doc .= "No implementado\n\n";
        }

        // Páginas
        if (!empty($info['pages'])) {
            $doc .= "## Páginas/Tabs\n\n";
            foreach ($info['pages'] as $page) {
                $doc .= "- `{$page}`\n";
            }
            $doc .= "\n";
        }

        // Vistas
        if (!empty($info['views'])) {
            $doc .= "## Archivos de Vista\n\n";
            foreach ($info['views'] as $view) {
                $doc .= "- `views/{$view}.php`\n";
            }
            $doc .= "\n";
        }

        // Hooks
        if (!empty($info['hooks']['actions']) || !empty($info['hooks']['filters'])) {
            $doc .= "## Hooks\n\n";

            if (!empty($info['hooks']['actions'])) {
                $doc .= "### Actions\n\n";
                foreach ($info['hooks']['actions'] as $action) {
                    $doc .= "- `{$action}`\n";
                }
                $doc .= "\n";
            }

            if (!empty($info['hooks']['filters'])) {
                $doc .= "### Filters\n\n";
                foreach ($info['hooks']['filters'] as $filter) {
                    $doc .= "- `{$filter}`\n";
                }
                $doc .= "\n";
            }
        }

        $doc .= "---\n\n";
        $doc .= "*Documentación generada automáticamente - " . date('Y-m-d H:i:s') . "*\n";

        return $doc;
    }
}

// Ejecutar
if (php_sapi_name() === 'cli' || defined('WP_CLI')) {
    $generator = new Flavor_Doc_Generator();
    $generator->generate_all();
} else {
    echo "Este script debe ejecutarse desde CLI.\n";
    echo "Usar: wp eval-file docs/generar-documentacion.php\n";
}
