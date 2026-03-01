<?php
/**
 * Diagnóstico completo de todos los módulos de Flavor Chat IA
 * Ejecutar visitando: /wp-content/plugins/flavor-chat-ia/diagnostico-modulos.php
 */

// Cargar WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Solo admins
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos');
}

// Capturar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

$resultados = [];
$errores_totales = 0;
$warnings_totales = 0;

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Módulos - Flavor Chat IA</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; background: #f5f5f5; }
        h1 { color: #1e3a5f; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        h2 { color: #374151; margin-top: 30px; }
        .resumen { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .resumen-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
        .resumen-card.ok { border-left: 4px solid #22c55e; }
        .resumen-card.warning { border-left: 4px solid #f59e0b; }
        .resumen-card.error { border-left: 4px solid #ef4444; }
        .resumen-card .valor { font-size: 36px; font-weight: bold; }
        .resumen-card .label { color: #6b7280; margin-top: 5px; }
        .modulo { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .modulo-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .modulo-header h3 { margin: 0; color: #1f2937; }
        .modulo-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .modulo-status.ok { background: #dcfce7; color: #166534; }
        .modulo-status.warning { background: #fef3c7; color: #92400e; }
        .modulo-status.error { background: #fee2e2; color: #991b1b; }
        .modulo-detalles { margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb; display: none; }
        .modulo.expanded .modulo-detalles { display: block; }
        .check { display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .check:last-child { border-bottom: none; }
        .check-icon { width: 24px; margin-right: 10px; text-align: center; }
        .check-ok { color: #22c55e; }
        .check-error { color: #ef4444; }
        .check-warning { color: #f59e0b; }
        .check-info { color: #3b82f6; }
        pre { background: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; margin: 10px 0; }
        .tabs-preview { background: #f9fafb; padding: 10px; border-radius: 6px; margin: 10px 0; }
        .tab-item { display: inline-block; padding: 5px 10px; background: #e5e7eb; border-radius: 4px; margin: 3px; font-size: 12px; }
        .shortcode-list { display: flex; flex-wrap: wrap; gap: 5px; margin: 10px 0; }
        .shortcode-item { padding: 3px 8px; background: #dbeafe; color: #1e40af; border-radius: 3px; font-size: 11px; font-family: monospace; }
        .shortcode-item.missing { background: #fee2e2; color: #991b1b; }
        .toggle-all { background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin: 10px 0; }
        .filter-buttons { margin: 20px 0; }
        .filter-btn { padding: 8px 16px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; margin-right: 5px; }
        .filter-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; }
    </style>
</head>
<body>
<h1>🔍 Diagnóstico de Módulos - Flavor Chat IA</h1>

<?php
global $wpdb;

// Obtener todos los módulos
$modulos_dir = dirname(__FILE__) . '/includes/modules/';
$modulos_encontrados = [];

if (is_dir($modulos_dir)) {
    $dirs = scandir($modulos_dir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        if (!is_dir($modulos_dir . $dir)) continue;

        // Buscar clase principal del módulo
        $class_file = $modulos_dir . $dir . '/class-' . $dir . '-module.php';
        if (file_exists($class_file)) {
            $modulos_encontrados[$dir] = [
                'path' => $modulos_dir . $dir,
                'class_file' => $class_file,
            ];
        }
    }
}

// Verificar Module Loader
$loader_activo = false;
$modulos_cargados = [];

if (class_exists('Flavor_Chat_Module_Loader')) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $loader_activo = true;
    $modulos_cargados = $loader->get_active_modules();
}

// Diagnosticar cada módulo
$modulos_ok = 0;
$modulos_warning = 0;
$modulos_error = 0;

foreach ($modulos_encontrados as $modulo_slug => $modulo_info) {
    $resultado = [
        'slug' => $modulo_slug,
        'nombre' => ucwords(str_replace('-', ' ', $modulo_slug)),
        'estado' => 'ok',
        'checks' => [],
        'errores' => [],
        'warnings' => [],
    ];

    // 1. Verificar si el módulo está cargado
    $esta_cargado = isset($modulos_cargados[$modulo_slug]) || isset($modulos_cargados[str_replace('-', '_', $modulo_slug)]);
    $resultado['checks']['cargado'] = $esta_cargado;
    if (!$esta_cargado) {
        $resultado['warnings'][] = 'Módulo no cargado por Module Loader';
    }

    // 2. Verificar tablas de BD (si hay install.php)
    $install_file = $modulo_info['path'] . '/install.php';
    if (file_exists($install_file)) {
        $resultado['checks']['install_file'] = true;

        // Buscar nombres de tablas en install.php
        $install_content = file_get_contents($install_file);
        preg_match_all("/wpdb->prefix\s*\.\s*['\"]([^'\"]+)['\"]/", $install_content, $matches);

        $tablas_modulo = $matches[1] ?? [];
        $tablas_ok = [];
        $tablas_faltantes = [];

        foreach ($tablas_modulo as $tabla) {
            $tabla_completa = $wpdb->prefix . $tabla;
            $existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_completa}'");
            if ($existe) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_completa}");
                $tablas_ok[$tabla] = $count;
            } else {
                $tablas_faltantes[] = $tabla;
            }
        }

        $resultado['tablas_ok'] = $tablas_ok;
        $resultado['tablas_faltantes'] = $tablas_faltantes;

        if (!empty($tablas_faltantes)) {
            $resultado['warnings'][] = 'Tablas faltantes: ' . implode(', ', $tablas_faltantes);
        }
    } else {
        $resultado['checks']['install_file'] = false;
    }

    // 3. Verificar carpeta views
    $views_dir = $modulo_info['path'] . '/views/';
    $resultado['views'] = [];
    if (is_dir($views_dir)) {
        $resultado['checks']['views_dir'] = true;
        $views = glob($views_dir . '*.php');
        foreach ($views as $view) {
            $view_name = basename($view, '.php');
            $resultado['views'][] = $view_name;
        }
    } else {
        $resultado['checks']['views_dir'] = false;
    }

    // 4. Verificar shortcodes del módulo
    $resultado['shortcodes'] = [];
    $resultado['shortcodes_faltantes'] = [];

    // Buscar shortcodes definidos en la clase del módulo
    $class_content = file_get_contents($modulo_info['class_file']);
    preg_match_all("/add_shortcode\s*\(\s*['\"]([^'\"]+)['\"]/", $class_content, $matches_sc);
    $shortcodes_definidos = $matches_sc[1] ?? [];

    foreach ($shortcodes_definidos as $sc) {
        if (shortcode_exists($sc)) {
            $resultado['shortcodes'][] = $sc;
        } else {
            $resultado['shortcodes_faltantes'][] = $sc;
        }
    }

    // 5. Verificar get_renderer_config o get_dashboard_tabs
    $tiene_renderer_config = strpos($class_content, 'get_renderer_config') !== false;
    $tiene_dashboard_tabs = strpos($class_content, 'get_dashboard_tabs') !== false;
    $resultado['checks']['renderer_config'] = $tiene_renderer_config;
    $resultado['checks']['dashboard_tabs'] = $tiene_dashboard_tabs;

    // 6. Probar instanciar la clase y obtener tabs
    $resultado['tabs'] = [];
    $resultado['tabs_error'] = null;

    // Normalizar nombre de clase
    $class_name_base = str_replace('-', '_', $modulo_slug);
    $posibles_clases = [
        'Flavor_Chat_' . ucwords($class_name_base, '_') . '_Module',
        'Flavor_' . ucwords($class_name_base, '_') . '_Module',
    ];

    foreach ($posibles_clases as $class_name) {
        if (class_exists($class_name)) {
            try {
                // Obtener instancia via Module Loader si está disponible
                if ($loader_activo) {
                    $module_instance = $loader->get_module($modulo_slug) ?? $loader->get_module(str_replace('-', '_', $modulo_slug));
                }

                if (!isset($module_instance) || !$module_instance) {
                    $module_instance = new $class_name();
                }

                // Intentar obtener tabs
                if (method_exists($module_instance, 'get_renderer_config')) {
                    $config = $module_instance->get_renderer_config();
                    if (isset($config['tabs'])) {
                        $resultado['tabs'] = array_keys($config['tabs']);
                    }
                } elseif (method_exists($module_instance, 'get_dashboard_tabs')) {
                    $tabs = $module_instance->get_dashboard_tabs();
                    $resultado['tabs'] = array_keys($tabs);
                }
            } catch (Throwable $e) {
                $resultado['tabs_error'] = $e->getMessage();
                $resultado['errores'][] = 'Error al obtener tabs: ' . $e->getMessage();
            }
            break;
        }
    }

    // 7. Verificar si hay problemas de sintaxis objeto/array en views
    $resultado['views_con_sintaxis_objeto'] = [];
    if (!empty($resultado['views'])) {
        foreach ($resultado['views'] as $view_name) {
            $view_file = $views_dir . $view_name . '.php';
            $view_content = file_get_contents($view_file);

            // Buscar foreach con acceso a propiedades de objeto
            if (preg_match('/foreach\s*\([^)]+as\s+\$([a-z_]+)\s*\).*?\$\1->/is', $view_content)) {
                $resultado['views_con_sintaxis_objeto'][] = $view_name;
            }
        }
    }

    // Determinar estado final
    if (!empty($resultado['errores'])) {
        $resultado['estado'] = 'error';
        $modulos_error++;
    } elseif (!empty($resultado['warnings']) || !empty($resultado['views_con_sintaxis_objeto'])) {
        $resultado['estado'] = 'warning';
        $modulos_warning++;
    } else {
        $modulos_ok++;
    }

    $resultados[$modulo_slug] = $resultado;
}

// Ordenar: errores primero, luego warnings, luego ok
uasort($resultados, function($a, $b) {
    $orden = ['error' => 0, 'warning' => 1, 'ok' => 2];
    return ($orden[$a['estado']] ?? 3) <=> ($orden[$b['estado']] ?? 3);
});

?>

<div class="resumen">
    <div class="resumen-card ok">
        <div class="valor"><?php echo count($modulos_encontrados); ?></div>
        <div class="label">Módulos encontrados</div>
    </div>
    <div class="resumen-card ok">
        <div class="valor"><?php echo $modulos_ok; ?></div>
        <div class="label">✅ OK</div>
    </div>
    <div class="resumen-card warning">
        <div class="valor"><?php echo $modulos_warning; ?></div>
        <div class="label">⚠️ Warnings</div>
    </div>
    <div class="resumen-card error">
        <div class="valor"><?php echo $modulos_error; ?></div>
        <div class="label">❌ Errores</div>
    </div>
</div>

<div class="filter-buttons">
    <button class="filter-btn active" data-filter="all">Todos</button>
    <button class="filter-btn" data-filter="error">Solo errores</button>
    <button class="filter-btn" data-filter="warning">Solo warnings</button>
    <button class="filter-btn" data-filter="ok">Solo OK</button>
</div>

<button class="toggle-all" onclick="toggleAll()">Expandir/Colapsar todos</button>

<h2>Detalle por módulo</h2>

<?php foreach ($resultados as $slug => $res): ?>
<div class="modulo" data-estado="<?php echo esc_attr($res['estado']); ?>" onclick="this.classList.toggle('expanded')">
    <div class="modulo-header">
        <h3><?php echo esc_html($res['nombre']); ?> <small style="color:#9ca3af;font-weight:normal;">(<?php echo esc_html($slug); ?>)</small></h3>
        <span class="modulo-status <?php echo esc_attr($res['estado']); ?>">
            <?php
            echo $res['estado'] === 'ok' ? '✅ OK' : ($res['estado'] === 'warning' ? '⚠️ Warning' : '❌ Error');
            ?>
        </span>
    </div>

    <div class="modulo-detalles">
        <!-- Checks básicos -->
        <h4>Verificaciones</h4>
        <?php foreach ($res['checks'] as $check => $valor): ?>
        <div class="check">
            <span class="check-icon <?php echo $valor ? 'check-ok' : 'check-warning'; ?>">
                <?php echo $valor ? '✓' : '○'; ?>
            </span>
            <span><?php echo ucwords(str_replace('_', ' ', $check)); ?></span>
        </div>
        <?php endforeach; ?>

        <!-- Tablas -->
        <?php if (!empty($res['tablas_ok']) || !empty($res['tablas_faltantes'])): ?>
        <h4>Tablas de BD</h4>
        <?php foreach ($res['tablas_ok'] ?? [] as $tabla => $count): ?>
        <div class="check">
            <span class="check-icon check-ok">✓</span>
            <span><code><?php echo esc_html($tabla); ?></code> - <?php echo $count; ?> registros</span>
        </div>
        <?php endforeach; ?>
        <?php foreach ($res['tablas_faltantes'] ?? [] as $tabla): ?>
        <div class="check">
            <span class="check-icon check-error">✗</span>
            <span><code><?php echo esc_html($tabla); ?></code> - NO EXISTE</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Tabs -->
        <?php if (!empty($res['tabs'])): ?>
        <h4>Tabs del Dashboard</h4>
        <div class="tabs-preview">
            <?php foreach ($res['tabs'] as $tab): ?>
            <span class="tab-item"><?php echo esc_html($tab); ?></span>
            <?php endforeach; ?>
        </div>
        <?php elseif ($res['tabs_error']): ?>
        <h4>Tabs del Dashboard</h4>
        <div class="check">
            <span class="check-icon check-error">✗</span>
            <span>Error: <?php echo esc_html($res['tabs_error']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Views -->
        <?php if (!empty($res['views'])): ?>
        <h4>Vistas (<?php echo count($res['views']); ?>)</h4>
        <div class="shortcode-list">
            <?php foreach ($res['views'] as $view): ?>
            <span class="shortcode-item"><?php echo esc_html($view); ?>.php</span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Sintaxis objeto en views -->
        <?php if (!empty($res['views_con_sintaxis_objeto'])): ?>
        <h4>⚠️ Vistas con sintaxis de objeto (posible problema)</h4>
        <div class="shortcode-list">
            <?php foreach ($res['views_con_sintaxis_objeto'] as $view): ?>
            <span class="shortcode-item missing"><?php echo esc_html($view); ?>.php</span>
            <?php endforeach; ?>
        </div>
        <p style="color:#92400e;font-size:12px;">Estas vistas usan <code>$item->propiedad</code> que podría fallar si reciben arrays.</p>
        <?php endif; ?>

        <!-- Shortcodes -->
        <?php if (!empty($res['shortcodes'])): ?>
        <h4>Shortcodes registrados</h4>
        <div class="shortcode-list">
            <?php foreach ($res['shortcodes'] as $sc): ?>
            <span class="shortcode-item">[<?php echo esc_html($sc); ?>]</span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($res['shortcodes_faltantes'])): ?>
        <h4>⚠️ Shortcodes NO registrados</h4>
        <div class="shortcode-list">
            <?php foreach ($res['shortcodes_faltantes'] as $sc): ?>
            <span class="shortcode-item missing">[<?php echo esc_html($sc); ?>]</span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Errores y Warnings -->
        <?php if (!empty($res['errores'])): ?>
        <h4>❌ Errores</h4>
        <pre><?php echo esc_html(implode("\n", $res['errores'])); ?></pre>
        <?php endif; ?>
        <?php if (!empty($res['warnings'])): ?>
        <h4>⚠️ Warnings</h4>
        <pre><?php echo esc_html(implode("\n", $res['warnings'])); ?></pre>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<script>
function toggleAll() {
    document.querySelectorAll('.modulo').forEach(m => m.classList.toggle('expanded'));
}

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;

        document.querySelectorAll('.modulo').forEach(m => {
            if (filter === 'all' || m.dataset.estado === filter) {
                m.style.display = 'block';
            } else {
                m.style.display = 'none';
            }
        });
    });
});
</script>

<h2>Resumen de problemas de sintaxis objeto/array</h2>
<p>Los siguientes archivos usan <code>$item->propiedad</code> dentro de foreach y podrían necesitar normalización:</p>
<pre><?php
$archivos_problema = [];
foreach ($resultados as $slug => $res) {
    foreach ($res['views_con_sintaxis_objeto'] ?? [] as $view) {
        $archivos_problema[] = "includes/modules/{$slug}/views/{$view}.php";
    }
}
echo implode("\n", $archivos_problema);
?></pre>

<p><strong>Total:</strong> <?php echo count($archivos_problema); ?> archivos</p>

</body>
</html>
