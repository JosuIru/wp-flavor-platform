<?php
/**
 * Verificar configuración del Archive Renderer para cada módulo
 * Ejecutar: /wp-content/plugins/flavor-chat-ia/check-renderer-config.php
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Sin permisos');
}

global $wpdb;

$plugin_path = dirname(__FILE__);

// Cargar Archive Renderer
require_once $plugin_path . '/includes/class-archive-renderer.php';
$renderer = new Flavor_Archive_Renderer();

// Obtener lista de módulos
$modules_path = $plugin_path . '/includes/modules/';
$modulos = [];

foreach (scandir($modules_path) as $dir) {
    if ($dir === '.' || $dir === '..') continue;
    if (!is_dir($modules_path . $dir)) continue;
    if (!file_exists($modules_path . $dir . '/class-' . $dir . '-module.php')) continue;
    $modulos[] = $dir;
}

sort($modulos);

// Usar reflection para obtener configuraciones privadas
$reflection = new ReflectionClass($renderer);

// Obtener get_module_table_config
$method = $reflection->getMethod('get_module_table_config');
$method->setAccessible(true);

// Verificar cada módulo
$con_config = [];
$sin_config = [];

foreach ($modulos as $modulo) {
    $config = $method->invoke($renderer, $modulo);

    if (!empty($config)) {
        $con_config[$modulo] = [
            'tabla' => $config['table'] ?? '?',
            'campos' => count($config['fields'] ?? []),
            'stats' => count($config['stats'] ?? []),
        ];

        // Verificar si la tabla existe
        $tabla_completa = $wpdb->prefix . $config['table'];
        $existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_completa}'");
        $con_config[$modulo]['tabla_existe'] = $existe ? true : false;

        if ($existe) {
            $con_config[$modulo]['registros'] = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_completa}");
        }
    } else {
        $sin_config[] = $modulo;
    }
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Configuración Archive Renderer</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 20px; background: #f5f5f5; max-width: 1200px; margin: 0 auto; }
        h1 { color: #1e3a5f; }
        h2 { color: #374151; margin-top: 30px; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat { background: white; padding: 20px; border-radius: 8px; text-align: center; flex: 1; }
        .stat .valor { font-size: 36px; font-weight: bold; }
        .stat.ok .valor { color: #22c55e; }
        .stat.warning .valor { color: #f59e0b; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .ok { color: #22c55e; }
        .error { color: #ef4444; }
        .chip { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin: 2px; }
        .chip.ok { background: #dcfce7; color: #166534; }
        .chip.error { background: #fee2e2; color: #991b1b; }
        pre { background: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 11px; }
    </style>
</head>
<body>
<h1>📊 Configuración Archive Renderer</h1>

<div class="stats">
    <div class="stat ok">
        <div class="valor"><?= count($con_config) ?></div>
        <div>Con configuración</div>
    </div>
    <div class="stat warning">
        <div class="valor"><?= count($sin_config) ?></div>
        <div>Sin configuración</div>
    </div>
    <div class="stat">
        <div class="valor"><?= count($modulos) ?></div>
        <div>Total módulos</div>
    </div>
</div>

<h2>✅ Módulos con configuración de tabla (<?= count($con_config) ?>)</h2>
<table>
    <tr>
        <th>Módulo</th>
        <th>Tabla</th>
        <th>Tabla existe</th>
        <th>Registros</th>
        <th>Campos</th>
        <th>Stats</th>
    </tr>
    <?php foreach ($con_config as $modulo => $info): ?>
    <tr>
        <td><strong><?= esc_html($modulo) ?></strong></td>
        <td><code><?= esc_html($info['tabla']) ?></code></td>
        <td class="<?= $info['tabla_existe'] ? 'ok' : 'error' ?>">
            <?= $info['tabla_existe'] ? '✅ Sí' : '❌ No' ?>
        </td>
        <td><?= $info['registros'] ?? '-' ?></td>
        <td><?= $info['campos'] ?></td>
        <td><?= $info['stats'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>⚠️ Módulos SIN configuración de tabla (<?= count($sin_config) ?>)</h2>
<p>Estos módulos necesitan añadir su configuración en <code>get_module_table_config()</code> del Archive Renderer:</p>

<div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0;">
    <?php foreach ($sin_config as $modulo): ?>
    <span class="chip error"><?= esc_html($modulo) ?></span>
    <?php endforeach; ?>
</div>

<h2>📝 Código para añadir configuraciones faltantes</h2>
<p>Añadir al array <code>$configs</code> en <code>get_module_table_config()</code>:</p>

<pre><?php
foreach ($sin_config as $modulo) {
    $tabla_slug = str_replace('-', '_', $modulo);
    echo "'{$modulo}' => [\n";
    echo "    'table'          => 'flavor_{$tabla_slug}',\n";
    echo "    'filter_field'   => 'estado',\n";
    echo "    'order_by'       => 'created_at DESC',\n";
    echo "    'fields'         => [\n";
    echo "        'id'          => 'id',\n";
    echo "        'titulo'      => 'titulo',\n";
    echo "        'descripcion' => 'descripcion',\n";
    echo "        'imagen'      => 'imagen',\n";
    echo "        'estado'      => 'estado',\n";
    echo "    ],\n";
    echo "    'stats' => [\n";
    echo "        ['label' => __('" . ucwords(str_replace('-', ' ', $modulo)) . "', 'flavor-platform'), 'icon' => '📋', 'color' => 'blue', 'count_where' => \"1=1\"],\n";
    echo "    ],\n";
    echo "],\n\n";
}
?></pre>

<h2>🔍 Probar presupuestos-participativos</h2>
<?php
// Probar específicamente presupuestos-participativos
$pp_config = $method->invoke($renderer, 'presupuestos-participativos');

if (!empty($pp_config)) {
    echo "<p class='ok'>✅ Configuración encontrada para presupuestos-participativos</p>";

    $tabla_pp = $wpdb->prefix . $pp_config['table'];
    $existe_tabla = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_pp}'");

    if ($existe_tabla) {
        $proyectos = $wpdb->get_results("SELECT id, titulo, estado, votos_recibidos FROM {$tabla_pp} LIMIT 10");
        echo "<p>Tabla: <code>{$tabla_pp}</code> - " . count($proyectos) . " proyectos encontrados</p>";
        echo "<pre>" . print_r($proyectos, true) . "</pre>";
    } else {
        echo "<p class='error'>❌ Tabla {$tabla_pp} NO existe</p>";
    }
} else {
    echo "<p class='error'>❌ NO hay configuración para presupuestos-participativos</p>";
}
?>

</body>
</html>
