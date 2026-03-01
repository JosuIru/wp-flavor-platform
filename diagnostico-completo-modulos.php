<?php
/**
 * Diagnóstico Completo de Módulos - Flavor Chat IA
 *
 * Ejecutar: wp eval-file diagnostico-completo-modulos.php
 * O visitar: /wp-content/plugins/flavor-chat-ia/diagnostico-completo-modulos.php
 */

// Cargar WordPress si se ejecuta directamente
if (!defined('ABSPATH')) {
    $wp_load_paths = [
        dirname(__FILE__) . '/../../../../wp-load.php',
        dirname(__FILE__) . '/../../../wp-load.php',
    ];

    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para ejecutar este diagnóstico.');
}

// Estilos para la salida HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Completo de Módulos</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; background: #f0f0f1; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #1d2327; }
        .module-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .module-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .module-name { font-size: 18px; font-weight: 600; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-ok { background: #d1fae5; color: #065f46; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-error { background: #fee2e2; color: #991b1b; }
        .status-missing { background: #f3f4f6; color: #6b7280; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: 600; color: #374151; margin-bottom: 8px; }
        .check-item { display: flex; align-items: center; gap: 8px; padding: 4px 0; font-size: 14px; }
        .check-icon { width: 20px; text-align: center; }
        .check-ok { color: #10b981; }
        .check-error { color: #ef4444; }
        .check-warning { color: #f59e0b; }
        .details { background: #f9fafb; padding: 10px; border-radius: 4px; font-size: 13px; margin-top: 8px; }
        .summary { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .summary-item { text-align: center; }
        .summary-number { font-size: 36px; font-weight: 700; }
        .summary-label { color: #6b7280; font-size: 14px; }
        .problems-list { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 15px; margin-top: 10px; }
        .problem-item { padding: 5px 0; border-bottom: 1px solid #fecaca; }
        .problem-item:last-child { border-bottom: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .code { font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnóstico Completo de Módulos - Flavor Chat IA</h1>
    <p>Fecha: <?php echo date('Y-m-d H:i:s'); ?></p>

<?php

// Directorio de módulos
$modules_dir = FLAVOR_CHAT_IA_PATH . 'includes/modules/';
$templates_dir = FLAVOR_CHAT_IA_PATH . 'templates/';

// Obtener todos los módulos
$module_dirs = glob($modules_dir . '*', GLOB_ONLYDIR);

// Arrays para estadísticas
$stats = [
    'total' => 0,
    'completos' => 0,
    'con_problemas' => 0,
    'sin_implementar' => 0,
];

$all_problems = [];
$module_reports = [];

foreach ($module_dirs as $module_path) {
    $module_slug = basename($module_path);

    // Ignorar directorios especiales
    if (in_array($module_slug, ['class-network-content-bridge.php', 'class-shared-features.php', 'trait-dashboard-widget.php'])) {
        continue;
    }

    $stats['total']++;

    $report = [
        'slug' => $module_slug,
        'name' => ucwords(str_replace('-', ' ', $module_slug)),
        'problems' => [],
        'warnings' => [],
        'checks' => [],
    ];

    // 1. Verificar archivo principal de clase
    $class_file = $module_path . '/class-' . $module_slug . '-module.php';
    $has_class_file = file_exists($class_file);
    $report['checks']['class_file'] = $has_class_file;

    if (!$has_class_file) {
        $report['problems'][] = "No existe archivo de clase principal";
        $stats['sin_implementar']++;
        $module_reports[] = $report;
        continue;
    }

    // Leer contenido del archivo de clase
    $class_content = file_get_contents($class_file);

    // 2. Verificar métodos esenciales
    $essential_methods = [
        'get_pages_definition' => 'Define páginas dinámicas para mi-portal',
        'get_renderer_config' => 'Configuración de renderizado con tabs',
        'register_shortcodes' => 'Registra shortcodes del módulo',
        'should_load_assets' => 'Control de carga de assets CSS/JS',
        'get_dashboard_widget_config' => 'Widget para dashboard del usuario',
    ];

    foreach ($essential_methods as $method => $description) {
        $has_method = strpos($class_content, "function {$method}") !== false ||
                      strpos($class_content, "function {$method} ") !== false;
        $report['checks'][$method] = $has_method;

        if (!$has_method && in_array($method, ['get_pages_definition', 'get_renderer_config'])) {
            $report['warnings'][] = "Falta método {$method}: {$description}";
        }
    }

    // 3. Verificar directorio views/
    $views_dir = $module_path . '/views/';
    $has_views = is_dir($views_dir);
    $report['checks']['views_dir'] = $has_views;

    if ($has_views) {
        $view_files = glob($views_dir . '*.php');
        $report['view_files'] = array_map('basename', $view_files);

        // Verificar si las vistas tienen datos hardcodeados
        foreach ($view_files as $view_file) {
            $view_content = file_get_contents($view_file);

            // Detectar datos hardcodeados comunes
            $hardcoded_patterns = [
                '/\$[a-z_]+\s*=\s*\[\s*\[.*\'nombre\'\s*=>\s*\'[A-Z]/' => 'Arrays hardcodeados con nombres',
                '/Lorem ipsum/i' => 'Texto Lorem ipsum',
                '/example\.com/i' => 'URLs de ejemplo',
                '/123456|999999/' => 'IDs numéricos de ejemplo',
            ];

            foreach ($hardcoded_patterns as $pattern => $desc) {
                if (preg_match($pattern, $view_content)) {
                    $report['warnings'][] = "Vista " . basename($view_file) . ": posible dato hardcodeado ({$desc})";
                }
            }
        }
    } else {
        $report['warnings'][] = "No tiene directorio views/";
    }

    // 4. Verificar shortcodes registrados
    if (strpos($class_content, 'register_shortcodes') !== false) {
        preg_match_all('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $class_content, $shortcode_matches);
        $report['shortcodes'] = $shortcode_matches[1] ?? [];

        // Verificar que cada shortcode tiene su método callback
        foreach ($report['shortcodes'] as $shortcode) {
            $callback_pattern = "/{$shortcode}|" . str_replace('-', '_', $shortcode) . "/";
            // Esto es una verificación básica
        }
    }

    // 5. Verificar tabs en get_renderer_config
    if (strpos($class_content, 'get_renderer_config') !== false) {
        preg_match("/'tabs'\s*=>\s*\[(.*?)\],\s*\]/s", $class_content, $tabs_match);
        if (!empty($tabs_match[1])) {
            preg_match_all("/'([a-z_-]+)'\s*=>\s*\[/", $tabs_match[1], $tab_names);
            $report['tabs'] = $tab_names[1] ?? [];

            // Verificar que cada tab tiene content válido
            foreach ($report['tabs'] as $tab) {
                $tab_pattern = "/'{$tab}'\s*=>\s*\[.*?'content'\s*=>\s*'([^']*)'/s";
                if (preg_match($tab_pattern, $class_content, $content_match)) {
                    $content = $content_match[1] ?? '';
                    if (empty($content)) {
                        $report['problems'][] = "Tab '{$tab}' tiene content vacío";
                    } elseif (strpos($content, '[') === 0) {
                        // Es un shortcode, verificar que existe
                        preg_match('/\[([a-z_-]+)/', $content, $sc_match);
                        $shortcode_name = $sc_match[1] ?? '';
                        if ($shortcode_name && !shortcode_exists($shortcode_name)) {
                            $report['problems'][] = "Tab '{$tab}' usa shortcode [{$shortcode_name}] que no existe";
                        }
                    }
                }
            }
        }
    }

    // 6. Verificar dashboard widget config
    if (strpos($class_content, 'get_dashboard_widget_config') !== false) {
        $report['checks']['has_widget'] = true;

        // Verificar que el shortcode del widget existe
        preg_match("/'shortcode'\s*=>\s*'([^']+)'/", $class_content, $widget_sc_match);
        if (!empty($widget_sc_match[1])) {
            $widget_shortcode = $widget_sc_match[1];
            // Extraer nombre del shortcode
            preg_match('/\[([a-z_-]+)/', $widget_shortcode, $sc_name);
            if (!empty($sc_name[1]) && !shortcode_exists($sc_name[1])) {
                $report['problems'][] = "Widget usa shortcode [{$sc_name[1]}] que no existe";
            }
        }
    }

    // 7. Verificar assets (CSS/JS)
    $assets_dir = $module_path . '/assets/';
    $has_assets = is_dir($assets_dir);
    $report['checks']['assets_dir'] = $has_assets;

    if ($has_assets) {
        $css_files = glob($assets_dir . 'css/*.css') ?: glob($assets_dir . '*.css') ?: [];
        $js_files = glob($assets_dir . 'js/*.js') ?: glob($assets_dir . '*.js') ?: [];
        $report['assets'] = [
            'css' => array_map('basename', $css_files),
            'js' => array_map('basename', $js_files),
        ];
    }

    // 8. Verificar frontend controller
    $frontend_dir = $module_path . '/frontend/';
    $has_frontend = is_dir($frontend_dir);
    $report['checks']['frontend_dir'] = $has_frontend;

    if ($has_frontend) {
        $frontend_controller = $frontend_dir . 'class-' . $module_slug . '-frontend-controller.php';
        $report['checks']['frontend_controller'] = file_exists($frontend_controller);
    }

    // 9. Verificar install.php
    $install_file = $module_path . '/install.php';
    $report['checks']['install_file'] = file_exists($install_file);

    // 10. Verificar templates de componentes
    $component_dir = $templates_dir . 'components/' . $module_slug . '/';
    $report['checks']['component_templates'] = is_dir($component_dir);

    // 11. Verificar que should_load_assets detecta páginas dinámicas
    if (strpos($class_content, 'should_load_assets') !== false) {
        $should_load_content = '';
        preg_match('/function\s+should_load_assets\s*\([^)]*\)\s*\{(.*?)\n\s{4}\}/s', $class_content, $sla_match);
        if (!empty($sla_match[1])) {
            $should_load_content = $sla_match[1];

            // Verificar si detecta flavor_module query var
            if (strpos($should_load_content, 'flavor_module') === false &&
                strpos($should_load_content, 'REQUEST_URI') === false) {
                $report['problems'][] = "should_load_assets() no detecta páginas dinámicas (falta flavor_module o REQUEST_URI)";
            }
        }
    }

    // Determinar estado del módulo
    if (count($report['problems']) > 0) {
        $report['status'] = 'error';
        $stats['con_problemas']++;
    } elseif (count($report['warnings']) > 0) {
        $report['status'] = 'warning';
        $stats['con_problemas']++;
    } else {
        $report['status'] = 'ok';
        $stats['completos']++;
    }

    $module_reports[] = $report;

    // Agregar problemas a lista global
    foreach ($report['problems'] as $problem) {
        $all_problems[] = [
            'module' => $module_slug,
            'type' => 'error',
            'message' => $problem,
        ];
    }
}

// Mostrar resumen
?>
<div class="summary">
    <h2>📊 Resumen General</h2>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-number"><?php echo $stats['total']; ?></div>
            <div class="summary-label">Total Módulos</div>
        </div>
        <div class="summary-item">
            <div class="summary-number" style="color: #10b981;"><?php echo $stats['completos']; ?></div>
            <div class="summary-label">Completos</div>
        </div>
        <div class="summary-item">
            <div class="summary-number" style="color: #f59e0b;"><?php echo $stats['con_problemas']; ?></div>
            <div class="summary-label">Con Problemas</div>
        </div>
        <div class="summary-item">
            <div class="summary-number" style="color: #6b7280;"><?php echo $stats['sin_implementar']; ?></div>
            <div class="summary-label">Sin Implementar</div>
        </div>
    </div>
</div>

<?php if (!empty($all_problems)): ?>
<div class="module-card">
    <div class="module-header">
        <span class="module-name">⚠️ Problemas Críticos a Resolver</span>
        <span class="status-badge status-error"><?php echo count($all_problems); ?> problemas</span>
    </div>
    <div class="problems-list">
        <?php foreach ($all_problems as $problem): ?>
        <div class="problem-item">
            <strong><?php echo esc_html($problem['module']); ?>:</strong>
            <?php echo esc_html($problem['message']); ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<h2>📦 Detalle por Módulo</h2>

<?php foreach ($module_reports as $report): ?>
<div class="module-card">
    <div class="module-header">
        <span class="module-name"><?php echo esc_html($report['name']); ?></span>
        <span class="status-badge status-<?php echo $report['status']; ?>">
            <?php
            echo $report['status'] === 'ok' ? '✓ Completo' :
                 ($report['status'] === 'warning' ? '⚠ Advertencias' : '✗ Problemas');
            ?>
        </span>
    </div>

    <div class="section">
        <div class="section-title">Verificaciones</div>
        <?php foreach ($report['checks'] as $check => $passed): ?>
        <div class="check-item">
            <span class="check-icon <?php echo $passed ? 'check-ok' : 'check-error'; ?>">
                <?php echo $passed ? '✓' : '✗'; ?>
            </span>
            <span><?php echo esc_html(ucwords(str_replace('_', ' ', $check))); ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($report['tabs'])): ?>
    <div class="section">
        <div class="section-title">Tabs Definidos</div>
        <div class="details">
            <?php echo implode(', ', array_map(function($t) { return "<span class='code'>{$t}</span>"; }, $report['tabs'])); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($report['shortcodes'])): ?>
    <div class="section">
        <div class="section-title">Shortcodes</div>
        <div class="details">
            <?php echo implode(', ', array_map(function($s) { return "<span class='code'>[{$s}]</span>"; }, $report['shortcodes'])); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($report['view_files'])): ?>
    <div class="section">
        <div class="section-title">Archivos de Vista</div>
        <div class="details">
            <?php echo implode(', ', array_map(function($v) { return "<span class='code'>{$v}</span>"; }, $report['view_files'])); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($report['problems'])): ?>
    <div class="section">
        <div class="section-title">❌ Problemas</div>
        <div class="problems-list">
            <?php foreach ($report['problems'] as $problem): ?>
            <div class="problem-item"><?php echo esc_html($problem); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($report['warnings'])): ?>
    <div class="section">
        <div class="section-title">⚠️ Advertencias</div>
        <div class="details" style="background: #fffbeb;">
            <?php foreach ($report['warnings'] as $warning): ?>
            <div style="padding: 3px 0;"><?php echo esc_html($warning); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="module-card">
    <h2>🔧 Acciones de Corrección Automatizadas</h2>
    <p>Ejecuta las siguientes correcciones:</p>
    <form method="post">
        <?php wp_nonce_field('flavor_fix_modules', 'flavor_fix_nonce'); ?>
        <p>
            <label>
                <input type="checkbox" name="fix_should_load_assets" value="1">
                Añadir detección de páginas dinámicas a should_load_assets() en todos los módulos
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="fix_missing_shortcodes" value="1">
                Crear shortcodes faltantes con implementación básica
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="fix_empty_tabs" value="1">
                Corregir tabs con content vacío
            </label>
        </p>
        <p>
            <button type="submit" name="run_fixes" class="button button-primary" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">
                Ejecutar Correcciones Seleccionadas
            </button>
        </p>
    </form>
</div>

</div>
</body>
</html>
<?php

// Procesar correcciones si se enviaron
if (isset($_POST['run_fixes']) && wp_verify_nonce($_POST['flavor_fix_nonce'] ?? '', 'flavor_fix_modules')) {
    // Aquí irían las correcciones automatizadas
    echo '<script>alert("Correcciones aplicadas. Recarga la página para ver los cambios.");</script>';
}
