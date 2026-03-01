<?php
/**
 * Diagnóstico COMPLETO de módulos Flavor Chat IA
 * Detecta necesidades y elementos faltantes por módulo
 *
 * Ejecutar: /wp-content/plugins/flavor-chat-ia/diagnostico-completo.php
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Sin permisos');
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

global $wpdb;

$plugin_path = dirname(__FILE__);
$modules_path = $plugin_path . '/includes/modules/';

// Componentes requeridos por cada tipo de funcionalidad
$requisitos_base = [
    'archivo_clase' => 'class-{modulo}-module.php',
    'metodos_minimos' => ['init', 'get_config'],
];

// Cargar el Module Loader para ver qué módulos están activos
$loader = null;
$modulos_activos = [];
if (class_exists('Flavor_Chat_Module_Loader')) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $modulos_activos = $loader->get_active_modules();
}

// Recolectar información de todos los módulos
$modulos = [];
$dirs = scandir($modules_path);

foreach ($dirs as $dir) {
    if ($dir === '.' || $dir === '..' || !is_dir($modules_path . $dir)) {
        continue;
    }

    // Verificar que sea un módulo válido (tiene clase principal)
    $class_file = $modules_path . $dir . '/class-' . $dir . '-module.php';
    if (!file_exists($class_file)) {
        continue;
    }

    $modulo = [
        'slug' => $dir,
        'nombre' => ucwords(str_replace('-', ' ', $dir)),
        'path' => $modules_path . $dir,
        'class_file' => $class_file,
        'diagnostico' => [],
        'faltantes' => [],
        'recomendaciones' => [],
        'estado' => 'ok',
    ];

    // ===== 1. VERIFICAR SI ESTÁ CARGADO =====
    $slug_underscore = str_replace('-', '_', $dir);
    $esta_cargado = isset($modulos_activos[$dir]) || isset($modulos_activos[$slug_underscore]);
    $modulo['diagnostico']['cargado'] = [
        'valor' => $esta_cargado,
        'mensaje' => $esta_cargado ? 'Cargado por Module Loader' : 'NO cargado',
    ];
    if (!$esta_cargado) {
        $modulo['faltantes'][] = 'Activación en Module Loader';
        $modulo['recomendaciones'][] = 'Verificar que el módulo esté habilitado en la configuración';
    }

    // ===== 2. LEER Y ANALIZAR LA CLASE PRINCIPAL =====
    $class_content = file_get_contents($class_file);

    // 2.1 Verificar nombre de clase
    preg_match('/class\s+(\w+)/', $class_content, $class_matches);
    $class_name = $class_matches[1] ?? null;
    $modulo['class_name'] = $class_name;

    // 2.2 Verificar métodos importantes
    $metodos_importantes = [
        'init' => 'Inicialización del módulo',
        'get_config' => 'Configuración básica',
        'get_renderer_config' => 'Configuración para Archive Renderer (tabs dinámicas)',
        'get_dashboard_tabs' => 'Tabs del dashboard (legacy)',
        'get_module_data' => 'Obtención de datos para listados',
        'enqueue_scripts' => 'Carga de scripts/estilos',
        'register_shortcodes' => 'Registro de shortcodes',
        'create_tables' => 'Creación de tablas',
        'maybe_create_tables' => 'Verificación/creación de tablas',
    ];

    $modulo['metodos'] = [];
    foreach ($metodos_importantes as $metodo => $descripcion) {
        $tiene = strpos($class_content, "function {$metodo}") !== false ||
                 strpos($class_content, "function {$metodo} ") !== false;
        $modulo['metodos'][$metodo] = [
            'existe' => $tiene,
            'descripcion' => $descripcion,
        ];
    }

    // 2.3 Verificar si tiene get_renderer_config (necesario para tabs modernas)
    if (!$modulo['metodos']['get_renderer_config']['existe']) {
        $modulo['faltantes'][] = 'Método get_renderer_config() para tabs dinámicas';
        $modulo['recomendaciones'][] = 'Implementar get_renderer_config() que devuelva configuración de tabs';
    }

    // ===== 3. VERIFICAR TABLAS DE BD =====
    $install_file = $modules_path . $dir . '/install.php';
    $modulo['tiene_install'] = file_exists($install_file);

    if ($modulo['tiene_install']) {
        $install_content = file_get_contents($install_file);

        // Buscar tablas definidas
        preg_match_all("/wpdb->prefix\s*\.\s*['\"]([^'\"]+)['\"]/", $install_content, $tabla_matches);
        $tablas_definidas = array_unique($tabla_matches[1] ?? []);

        $modulo['tablas'] = [];
        foreach ($tablas_definidas as $tabla) {
            $tabla_completa = $wpdb->prefix . $tabla;
            $existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_completa}'");

            if ($existe) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_completa}");
                $modulo['tablas'][$tabla] = ['existe' => true, 'registros' => $count];
            } else {
                $modulo['tablas'][$tabla] = ['existe' => false, 'registros' => 0];
                $modulo['faltantes'][] = "Tabla: {$tabla}";
                $modulo['recomendaciones'][] = "Ejecutar install.php o crear tabla {$tabla}";
            }
        }
    }

    // ===== 4. VERIFICAR SHORTCODES =====
    // Buscar shortcodes definidos en el módulo
    preg_match_all("/add_shortcode\s*\(\s*['\"]([^'\"]+)['\"]/", $class_content, $sc_matches);
    $shortcodes_definidos = array_unique($sc_matches[1] ?? []);

    $modulo['shortcodes'] = [];
    foreach ($shortcodes_definidos as $sc) {
        $registrado = shortcode_exists($sc);
        $modulo['shortcodes'][$sc] = $registrado;
        if (!$registrado) {
            $modulo['faltantes'][] = "Shortcode [{$sc}] no registrado";
        }
    }

    // ===== 5. VERIFICAR VIEWS =====
    $views_path = $modules_path . $dir . '/views/';
    $modulo['views'] = [];
    $modulo['views_con_errores'] = [];

    if (is_dir($views_path)) {
        $views = glob($views_path . '*.php');
        foreach ($views as $view_file) {
            $view_name = basename($view_file, '.php');
            $view_content = file_get_contents($view_file);

            // Detectar uso de sintaxis de objeto en foreach
            $usa_objeto = false;
            $lineas_problema = [];

            $lines = explode("\n", $view_content);
            $in_foreach = false;
            $foreach_var = '';

            foreach ($lines as $num => $line) {
                // Detectar inicio de foreach
                if (preg_match('/foreach\s*\([^)]+as\s+\$([a-z_]+)/', $line, $m)) {
                    $in_foreach = true;
                    $foreach_var = $m[1];
                }

                // Detectar uso de $var->propiedad
                if ($in_foreach && $foreach_var) {
                    if (preg_match('/\$' . preg_quote($foreach_var) . '->/', $line)) {
                        $usa_objeto = true;
                        $lineas_problema[] = $num + 1;
                    }
                }

                // Detectar fin de foreach
                if (strpos($line, 'endforeach') !== false || preg_match('/^\s*\}/', $line)) {
                    if ($in_foreach) {
                        $in_foreach = false;
                        $foreach_var = '';
                    }
                }
            }

            $modulo['views'][$view_name] = [
                'sintaxis_objeto' => $usa_objeto,
                'lineas_problema' => $lineas_problema,
            ];

            if ($usa_objeto) {
                $modulo['views_con_errores'][] = [
                    'archivo' => $view_name . '.php',
                    'lineas' => $lineas_problema,
                ];
            }
        }
    }

    if (!empty($modulo['views_con_errores'])) {
        $modulo['faltantes'][] = 'Normalización array/objeto en ' . count($modulo['views_con_errores']) . ' vistas';
        $modulo['recomendaciones'][] = 'Usar is_array() para normalizar datos en vistas';
    }

    // ===== 6. VERIFICAR FRONTEND =====
    $frontend_path = $modules_path . $dir . '/frontend/';
    $modulo['tiene_frontend'] = is_dir($frontend_path);

    if ($modulo['tiene_frontend']) {
        $modulo['frontend_files'] = array_map('basename', glob($frontend_path . '*.php'));
    }

    // ===== 7. VERIFICAR TEMPLATES =====
    $templates_base = $plugin_path . '/templates/frontend/' . $dir . '/';
    $modulo['tiene_templates'] = is_dir($templates_base);

    if ($modulo['tiene_templates']) {
        $modulo['templates'] = array_map('basename', glob($templates_base . '*.php'));
    }

    // ===== 8. VERIFICAR ASSETS =====
    $assets_path = $modules_path . $dir . '/assets/';
    $modulo['tiene_assets'] = is_dir($assets_path);

    if ($modulo['tiene_assets']) {
        $modulo['assets'] = [
            'css' => array_map('basename', glob($assets_path . '*.css') + glob($assets_path . 'css/*.css')),
            'js' => array_map('basename', glob($assets_path . '*.js') + glob($assets_path . 'js/*.js')),
        ];
    }

    // ===== 9. VERIFICAR CRUD =====
    // Buscar si usa Dynamic CRUD
    $usa_crud = strpos($class_content, 'Flavor_Dynamic_CRUD') !== false ||
                strpos($class_content, 'flavor_crud') !== false;
    $modulo['diagnostico']['usa_crud'] = [
        'valor' => $usa_crud,
        'mensaje' => $usa_crud ? 'Integrado con Dynamic CRUD' : 'No usa CRUD dinámico',
    ];

    // ===== 10. DETECTAR INTEGRACIONES =====
    $integraciones = [];

    if (strpos($class_content, 'WooCommerce') !== false || strpos($class_content, 'woocommerce') !== false) {
        $integraciones[] = 'WooCommerce';
    }
    if (strpos($class_content, 'wp_mail') !== false) {
        $integraciones[] = 'Email (wp_mail)';
    }
    if (strpos($class_content, 'BuddyPress') !== false) {
        $integraciones[] = 'BuddyPress';
    }
    if (strpos($class_content, 'WPML') !== false) {
        $integraciones[] = 'WPML';
    }

    $modulo['integraciones'] = $integraciones;

    // ===== DETERMINAR ESTADO FINAL =====
    $num_faltantes = count($modulo['faltantes']);
    if ($num_faltantes === 0) {
        $modulo['estado'] = 'ok';
    } elseif ($num_faltantes <= 2) {
        $modulo['estado'] = 'warning';
    } else {
        $modulo['estado'] = 'error';
    }

    $modulos[$dir] = $modulo;
}

// Ordenar por estado (errores primero)
uasort($modulos, function($a, $b) {
    $orden = ['error' => 0, 'warning' => 1, 'ok' => 2];
    return ($orden[$a['estado']] ?? 3) <=> ($orden[$b['estado']] ?? 3);
});

// Estadísticas generales
$stats = [
    'total' => count($modulos),
    'ok' => count(array_filter($modulos, fn($m) => $m['estado'] === 'ok')),
    'warning' => count(array_filter($modulos, fn($m) => $m['estado'] === 'warning')),
    'error' => count(array_filter($modulos, fn($m) => $m['estado'] === 'error')),
    'con_renderer_config' => count(array_filter($modulos, fn($m) => $m['metodos']['get_renderer_config']['existe'] ?? false)),
    'con_views_problema' => count(array_filter($modulos, fn($m) => !empty($m['views_con_errores']))),
];

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Completo - Flavor Chat IA</title>
    <style>
        :root { --ok: #22c55e; --warning: #f59e0b; --error: #ef4444; --info: #3b82f6; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f3f4f6; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #1e3a5f; border-bottom: 3px solid var(--info); padding-bottom: 15px; margin-bottom: 30px; }
        h2 { color: #374151; margin-top: 40px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); text-align: center; }
        .stat-card h3 { margin: 0 0 5px; font-size: 14px; color: #6b7280; font-weight: 500; }
        .stat-card .value { font-size: 42px; font-weight: 700; }
        .stat-card.ok .value { color: var(--ok); }
        .stat-card.warning .value { color: var(--warning); }
        .stat-card.error .value { color: var(--error); }
        .stat-card.info .value { color: var(--info); }

        .filters { margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border: 2px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
        .filter-btn:hover { border-color: var(--info); }
        .filter-btn.active { background: var(--info); color: white; border-color: var(--info); }

        .modulo { background: white; margin: 15px 0; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .modulo-header { padding: 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; border-bottom: 1px solid transparent; transition: all 0.2s; }
        .modulo-header:hover { background: #f9fafb; }
        .modulo.expanded .modulo-header { border-bottom-color: #e5e7eb; }
        .modulo-header h3 { margin: 0; color: #1f2937; display: flex; align-items: center; gap: 10px; }
        .modulo-header .slug { color: #9ca3af; font-weight: 400; font-size: 14px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        .badge.error { background: #fee2e2; color: #991b1b; }

        .modulo-body { display: none; padding: 20px; background: #fafafa; }
        .modulo.expanded .modulo-body { display: block; }

        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .section-title::before { content: ''; width: 4px; height: 16px; background: var(--info); border-radius: 2px; }

        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
        .item { padding: 10px 14px; background: white; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .item.ok { border-color: var(--ok); background: #f0fdf4; }
        .item.error { border-color: var(--error); background: #fef2f2; }
        .item.warning { border-color: var(--warning); background: #fffbeb; }
        .item-icon { font-size: 16px; }

        .faltantes { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .faltantes-title { color: var(--error); font-weight: 600; margin-bottom: 10px; }
        .faltantes ul { margin: 0; padding-left: 20px; }
        .faltantes li { color: #991b1b; margin: 5px 0; }

        .recomendaciones { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px; }
        .recomendaciones-title { color: var(--info); font-weight: 600; margin-bottom: 10px; }
        .recomendaciones ul { margin: 0; padding-left: 20px; }
        .recomendaciones li { color: #1e40af; margin: 5px 0; }

        .metodos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
        .metodo { padding: 8px 12px; background: white; border-radius: 6px; font-size: 12px; font-family: monospace; display: flex; justify-content: space-between; border: 1px solid #e5e7eb; }
        .metodo.existe { background: #f0fdf4; border-color: var(--ok); }
        .metodo .check { color: var(--ok); }
        .metodo .cross { color: var(--error); }

        .views-problemas { margin-top: 15px; }
        .view-problema { background: #fef2f2; padding: 10px; border-radius: 6px; margin: 5px 0; font-size: 12px; }
        .view-problema code { background: #fee2e2; padding: 2px 6px; border-radius: 3px; }

        pre { background: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px; }

        .toggle-all { background: var(--info); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; }
        .toggle-all:hover { background: #2563eb; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .items-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnóstico Completo de Módulos</h1>

    <div class="stats-grid">
        <div class="stat-card info">
            <h3>Total módulos</h3>
            <div class="value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card ok">
            <h3>Sin problemas</h3>
            <div class="value"><?= $stats['ok'] ?></div>
        </div>
        <div class="stat-card warning">
            <h3>Advertencias</h3>
            <div class="value"><?= $stats['warning'] ?></div>
        </div>
        <div class="stat-card error">
            <h3>Con errores</h3>
            <div class="value"><?= $stats['error'] ?></div>
        </div>
        <div class="stat-card info">
            <h3>Con get_renderer_config</h3>
            <div class="value"><?= $stats['con_renderer_config'] ?></div>
        </div>
        <div class="stat-card warning">
            <h3>Views con problemas</h3>
            <div class="value"><?= $stats['con_views_problema'] ?></div>
        </div>
    </div>

    <div class="filters">
        <button class="filter-btn active" data-filter="all">Todos (<?= $stats['total'] ?>)</button>
        <button class="filter-btn" data-filter="error">❌ Errores (<?= $stats['error'] ?>)</button>
        <button class="filter-btn" data-filter="warning">⚠️ Warnings (<?= $stats['warning'] ?>)</button>
        <button class="filter-btn" data-filter="ok">✅ OK (<?= $stats['ok'] ?>)</button>
    </div>

    <button class="toggle-all" onclick="toggleAll()">📂 Expandir/Colapsar todos</button>

    <h2>Detalle por módulo</h2>

    <?php foreach ($modulos as $slug => $modulo): ?>
    <div class="modulo" data-estado="<?= $modulo['estado'] ?>" onclick="event.currentTarget.classList.toggle('expanded')">
        <div class="modulo-header">
            <h3>
                <?= esc_html($modulo['nombre']) ?>
                <span class="slug">(<?= esc_html($slug) ?>)</span>
            </h3>
            <span class="badge <?= $modulo['estado'] ?>">
                <?= $modulo['estado'] === 'ok' ? '✅ OK' : ($modulo['estado'] === 'warning' ? '⚠️ '.count($modulo['faltantes']).' items' : '❌ '.count($modulo['faltantes']).' items') ?>
            </span>
        </div>

        <div class="modulo-body">
            <?php if (!empty($modulo['faltantes'])): ?>
            <div class="faltantes">
                <div class="faltantes-title">❌ Elementos faltantes</div>
                <ul>
                    <?php foreach ($modulo['faltantes'] as $faltante): ?>
                    <li><?= esc_html($faltante) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($modulo['recomendaciones'])): ?>
            <div class="recomendaciones">
                <div class="recomendaciones-title">💡 Recomendaciones</div>
                <ul>
                    <?php foreach ($modulo['recomendaciones'] as $rec): ?>
                    <li><?= esc_html($rec) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Métodos -->
            <div class="section">
                <div class="section-title">Métodos de la clase</div>
                <div class="metodos-grid">
                    <?php foreach ($modulo['metodos'] as $metodo => $info): ?>
                    <div class="metodo <?= $info['existe'] ? 'existe' : '' ?>">
                        <span><?= $metodo ?>()</span>
                        <span class="<?= $info['existe'] ? 'check' : 'cross' ?>"><?= $info['existe'] ? '✓' : '✗' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tablas -->
            <?php if (!empty($modulo['tablas'])): ?>
            <div class="section">
                <div class="section-title">Tablas de BD</div>
                <div class="items-grid">
                    <?php foreach ($modulo['tablas'] as $tabla => $info): ?>
                    <div class="item <?= $info['existe'] ? 'ok' : 'error' ?>">
                        <span class="item-icon"><?= $info['existe'] ? '✓' : '✗' ?></span>
                        <span><?= esc_html($tabla) ?></span>
                        <?php if ($info['existe']): ?>
                        <span style="margin-left:auto;color:#6b7280">(<?= $info['registros'] ?> reg)</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Shortcodes -->
            <?php if (!empty($modulo['shortcodes'])): ?>
            <div class="section">
                <div class="section-title">Shortcodes</div>
                <div class="items-grid">
                    <?php foreach ($modulo['shortcodes'] as $sc => $registrado): ?>
                    <div class="item <?= $registrado ? 'ok' : 'error' ?>">
                        <span class="item-icon"><?= $registrado ? '✓' : '✗' ?></span>
                        <code>[<?= esc_html($sc) ?>]</code>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Views -->
            <?php if (!empty($modulo['views'])): ?>
            <div class="section">
                <div class="section-title">Vistas (<?= count($modulo['views']) ?>)</div>
                <div class="items-grid">
                    <?php foreach ($modulo['views'] as $view => $info): ?>
                    <div class="item <?= $info['sintaxis_objeto'] ? 'warning' : 'ok' ?>">
                        <span class="item-icon"><?= $info['sintaxis_objeto'] ? '⚠️' : '✓' ?></span>
                        <span><?= esc_html($view) ?>.php</span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($modulo['views_con_errores'])): ?>
                <div class="views-problemas">
                    <strong>⚠️ Vistas con sintaxis $item->propiedad que podrían fallar:</strong>
                    <?php foreach ($modulo['views_con_errores'] as $view_err): ?>
                    <div class="view-problema">
                        <code><?= esc_html($view_err['archivo']) ?></code>
                        líneas: <?= implode(', ', array_slice($view_err['lineas'], 0, 10)) ?><?= count($view_err['lineas']) > 10 ? '...' : '' ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Integraciones -->
            <?php if (!empty($modulo['integraciones'])): ?>
            <div class="section">
                <div class="section-title">Integraciones detectadas</div>
                <div class="items-grid">
                    <?php foreach ($modulo['integraciones'] as $integ): ?>
                    <div class="item">
                        <span class="item-icon">🔗</span>
                        <?= esc_html($integ) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recursos -->
            <div class="section">
                <div class="section-title">Recursos</div>
                <div class="items-grid">
                    <div class="item <?= $modulo['tiene_install'] ? 'ok' : '' ?>">
                        <span class="item-icon"><?= $modulo['tiene_install'] ? '✓' : '○' ?></span>
                        install.php
                    </div>
                    <div class="item <?= $modulo['tiene_frontend'] ? 'ok' : '' ?>">
                        <span class="item-icon"><?= $modulo['tiene_frontend'] ? '✓' : '○' ?></span>
                        /frontend/
                    </div>
                    <div class="item <?= $modulo['tiene_templates'] ? 'ok' : '' ?>">
                        <span class="item-icon"><?= $modulo['tiene_templates'] ? '✓' : '○' ?></span>
                        /templates/
                    </div>
                    <div class="item <?= $modulo['tiene_assets'] ? 'ok' : '' ?>">
                        <span class="item-icon"><?= $modulo['tiene_assets'] ? '✓' : '○' ?></span>
                        /assets/
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <h2>📋 Resumen de acciones necesarias</h2>
    <pre><?php
    $acciones = [];
    foreach ($modulos as $slug => $m) {
        if (!empty($m['faltantes'])) {
            $acciones[] = "\n=== {$m['nombre']} ({$slug}) ===";
            foreach ($m['faltantes'] as $f) {
                $acciones[] = "  - {$f}";
            }
        }
    }
    echo implode("\n", $acciones) ?: "¡Todos los módulos están OK!";
    ?></pre>
</div>

<script>
function toggleAll() {
    const modulos = document.querySelectorAll('.modulo');
    const expanded = document.querySelector('.modulo.expanded');
    modulos.forEach(m => {
        if (expanded) {
            m.classList.remove('expanded');
        } else {
            m.classList.add('expanded');
        }
    });
}

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('.modulo').forEach(m => {
            m.style.display = (filter === 'all' || m.dataset.estado === filter) ? '' : 'none';
        });
    });
});
</script>
</body>
</html>
