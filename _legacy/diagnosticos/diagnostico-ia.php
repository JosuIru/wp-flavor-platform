<?php
/**
 * Diagnóstico de Configuración de IA
 *
 * Ejecutar: wp-content/plugins/flavor-chat-ia/diagnostico-ia.php
 */

// Cargar WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Solo admins
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para ver esta página.');
}

// Cargar encriptación si no está cargada
if (!class_exists('Flavor_API_Key_Encryption')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/security/class-api-key-encryption.php';
}

// Obtener configuración
$settings = get_option('flavor_chat_ia_settings', []);

// Helper para mostrar estado
function estado_bool($valor) {
    return $valor ? '✅ Sí' : '❌ No';
}

function mostrar_api_key($key) {
    if (empty($key)) {
        return '❌ No configurada';
    }

    $encryption = Flavor_API_Key_Encryption::get_instance();
    $is_encrypted = $encryption->is_encrypted($key);
    $decrypted = $encryption->decrypt($key);

    if (empty($decrypted)) {
        return '⚠️ Configurada pero no se puede desencriptar';
    }

    $masked = substr($decrypted, 0, 8) . '...' . substr($decrypted, -4);
    $status = $is_encrypted ? '🔒 Encriptada' : '🔓 Sin encriptar';

    return "✅ {$masked} ({$status})";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de IA - Flavor Platform</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #1e3a5f; }
        h2 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 5px; }
        .section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .item { padding: 8px 0; border-bottom: 1px solid #eee; }
        .item:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #333; min-width: 200px; display: inline-block; }
        .value { color: #666; }
        .success { color: #46b450; }
        .warning { color: #ffb900; }
        .error { color: #dc3232; }
        code { background: #eee; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        pre { background: #263238; color: #aabbc3; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .test-btn { background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        .test-btn:hover { background: #005a87; }
        .test-result { margin-top: 15px; padding: 15px; border-radius: 5px; }
        .test-result.success { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-result.error { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de IA - Flavor Platform</h1>

    <div class="section">
        <h2>📋 Configuración General</h2>
        <div class="item">
            <span class="label">Chat IA Habilitado:</span>
            <span class="value"><?php echo estado_bool($settings['enabled'] ?? false); ?></span>
        </div>
        <div class="item">
            <span class="label">Proveedor Activo (default):</span>
            <span class="value"><code><?php echo esc_html($settings['active_provider'] ?? 'claude'); ?></code></span>
        </div>
        <div class="item">
            <span class="label">Proveedor Frontend:</span>
            <span class="value"><code><?php echo esc_html($settings['ia_provider_frontend'] ?? 'default'); ?></code></span>
        </div>
        <div class="item">
            <span class="label">Proveedor Backend:</span>
            <span class="value"><code><?php echo esc_html($settings['ia_provider_backend'] ?? 'default'); ?></code></span>
        </div>
    </div>

    <div class="section">
        <h2>🔑 Estado de API Keys</h2>
        <div class="item">
            <span class="label">Claude (api_key legacy):</span>
            <span class="value"><?php echo mostrar_api_key($settings['api_key'] ?? ''); ?></span>
        </div>
        <div class="item">
            <span class="label">Claude (claude_api_key):</span>
            <span class="value"><?php echo mostrar_api_key($settings['claude_api_key'] ?? ''); ?></span>
        </div>
        <div class="item">
            <span class="label">OpenAI:</span>
            <span class="value"><?php echo mostrar_api_key($settings['openai_api_key'] ?? ''); ?></span>
        </div>
        <div class="item">
            <span class="label">DeepSeek:</span>
            <span class="value"><?php echo mostrar_api_key($settings['deepseek_api_key'] ?? ''); ?></span>
        </div>
        <div class="item">
            <span class="label">Mistral:</span>
            <span class="value"><?php echo mostrar_api_key($settings['mistral_api_key'] ?? ''); ?></span>
        </div>
    </div>

    <div class="section">
        <h2>⚙️ Modelos Configurados</h2>
        <div class="item">
            <span class="label">Claude:</span>
            <span class="value"><code><?php echo esc_html($settings['claude_model'] ?? 'claude-sonnet-4-20250514'); ?></code></span>
        </div>
        <div class="item">
            <span class="label">OpenAI:</span>
            <span class="value"><code><?php echo esc_html($settings['openai_model'] ?? 'gpt-4o-mini'); ?></code></span>
        </div>
        <div class="item">
            <span class="label">DeepSeek:</span>
            <span class="value"><code><?php echo esc_html($settings['deepseek_model'] ?? 'deepseek-chat'); ?></code></span>
        </div>
        <div class="item">
            <span class="label">Mistral:</span>
            <span class="value"><code><?php echo esc_html($settings['mistral_model'] ?? 'mistral-small-latest'); ?></code></span>
        </div>
    </div>

    <div class="section">
        <h2>🔧 Estado de Engines</h2>
        <?php
        // Cargar todos los engines para el diagnóstico
        $engines_dir = FLAVOR_CHAT_IA_PATH . 'includes/engines/';

        // Interface y base
        if (file_exists($engines_dir . 'interface-ai-engine.php')) {
            require_once $engines_dir . 'interface-ai-engine.php';
            echo '<div class="item"><span class="label">Interface AI Engine:</span><span class="value success">✅ Cargada</span></div>';
        }

        // Engine Manager
        if (file_exists($engines_dir . 'class-engine-manager.php')) {
            require_once $engines_dir . 'class-engine-manager.php';
            echo '<div class="item"><span class="label">Engine Manager:</span><span class="value success">✅ Cargado</span></div>';
        }

        // Cargar engines individuales
        $engine_files = [
            'claude' => 'class-engine-claude.php',
            'openai' => 'class-engine-openai.php',
            'deepseek' => 'class-engine-deepseek.php',
            'mistral' => 'class-engine-mistral.php',
        ];

        foreach ($engine_files as $name => $file) {
            if (file_exists($engines_dir . $file)) {
                require_once $engines_dir . $file;
                echo '<div class="item"><span class="label">Engine ' . ucfirst($name) . ':</span><span class="value success">✅ Cargado</span></div>';
            } else {
                echo '<div class="item"><span class="label">Engine ' . ucfirst($name) . ':</span><span class="value error">❌ No encontrado</span></div>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>🧪 Test de Engine Manager</h2>
        <?php
        if (class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $engines = $manager->get_engines();

            echo '<div class="item"><span class="label">Engines Registrados:</span><span class="value"><code>' . implode(', ', array_keys($engines)) . '</code></span></div>';

            foreach ($engines as $id => $engine) {
                $configured = $engine->is_configured() ? '✅ Configurado' : '❌ No configurado';
                $supports_tools = $engine->supports_tools() ? '✅' : '❌';
                echo '<div class="item">';
                echo '<span class="label">' . $engine->get_name() . ':</span>';
                echo '<span class="value">' . $configured . ' | Tools: ' . $supports_tools . '</span>';
                echo '</div>';
            }

            // Engines configurados para fallback
            echo '<h3>🔄 Sistema de Fallback</h3>';
            $configured_engines = $manager->get_configured_engines();
            $fallback_order = $manager->get_fallback_order();
            $saved_order = $settings['fallback_order'] ?? [];

            if (count($configured_engines) > 1) {
                echo '<div class="item success">';
                echo '<span class="label">Fallback Activo:</span>';
                echo '<span class="value">✅ ' . count($configured_engines) . ' engines disponibles</span>';
                echo '</div>';
                echo '<div class="item">';
                echo '<span class="label">Orden de prioridad:</span>';
                echo '<span class="value"><code>' . implode(' → ', $fallback_order) . '</code></span>';
                echo '</div>';
                if (!empty($saved_order)) {
                    echo '<div class="item">';
                    echo '<span class="label">Configurado por usuario:</span>';
                    echo '<span class="value success">✅ Orden personalizado</span>';
                    echo '</div>';
                } else {
                    echo '<div class="item">';
                    echo '<span class="label">Configurado por usuario:</span>';
                    echo '<span class="value">⚡ Orden automático (configúralo en Proveedores IA)</span>';
                    echo '</div>';
                }
                echo '<p style="color:#666;font-size:13px;margin-top:5px;">Si el primer engine falla, se usará el siguiente automáticamente.</p>';
            } elseif (count($configured_engines) === 1) {
                echo '<div class="item warning">';
                echo '<span class="label">Fallback:</span>';
                echo '<span class="value">⚠️ Solo 1 engine configurado. Configura otro para tener fallback.</span>';
                echo '</div>';
            } else {
                echo '<div class="item error">';
                echo '<span class="label">Fallback:</span>';
                echo '<span class="value">❌ Ningún engine configurado</span>';
                echo '</div>';
            }

            // Motor activo por contexto
            echo '<h3>Motor Activo por Contexto</h3>';
            $contexts = ['default', 'frontend', 'backend'];
            foreach ($contexts as $context) {
                $active = $manager->get_active_engine($context);
                $config = $manager->get_context_config($context);
                echo '<div class="item">';
                echo '<span class="label">Contexto ' . ucfirst($context) . ':</span>';
                echo '<span class="value">';
                if ($active) {
                    echo '<code>' . $active->get_name() . '</code>';
                    echo ' (provider: ' . ($config['provider'] ?? 'N/A') . ', model: ' . ($config['model'] ?? 'default') . ')';
                    echo $active->is_configured() ? ' ✅' : ' ❌ Sin API key';
                } else {
                    echo '❌ No hay motor activo';
                }
                echo '</span></div>';
            }
        } else {
            echo '<div class="item error">❌ Flavor_Engine_Manager no está disponible</div>';
        }
        ?>
    </div>

    <div class="section">
        <h2>🚀 Test de Conexión</h2>
        <p>Haz clic en un botón para probar la conexión con el proveedor:</p>

        <button class="test-btn" onclick="testProvider('mistral')">🧪 Probar Mistral</button>
        <button class="test-btn" onclick="testProvider('deepseek')">🧪 Probar DeepSeek</button>
        <button class="test-btn" onclick="testProvider('claude')">🧪 Probar Claude</button>
        <button class="test-btn" onclick="testProvider('openai')">🧪 Probar OpenAI</button>

        <div id="test-result"></div>

        <h3 style="margin-top:20px;">📱 Test de Sesión (chat_ia_init_session)</h3>
        <button class="test-btn" onclick="testSession()">🧪 Probar Crear Sesión</button>
        <div id="session-result"></div>
    </div>

    <div class="section">
        <h2>🔎 Diagnóstico de Carga de Engine</h2>
        <?php
        // Simular la carga del engine para debug
        echo '<h3>Simulación de carga de config para cada engine:</h3>';

        $providers = ['claude', 'openai', 'deepseek', 'mistral'];
        $settings = get_option('flavor_chat_ia_settings', []);
        $encryption = class_exists('Flavor_API_Key_Encryption')
            ? Flavor_API_Key_Encryption::get_instance()
            : null;

        foreach ($providers as $provider_id) {
            echo '<div class="item">';
            echo '<span class="label">' . ucfirst($provider_id) . ':</span>';

            // Simular load_config()
            $encrypted_key = $settings[$provider_id . '_api_key'] ?? '';

            // Compatibilidad con api_key legacy (Claude)
            if ($provider_id === 'claude' && empty($encrypted_key)) {
                $encrypted_key = $settings['api_key'] ?? '';
            }

            if (empty($encrypted_key)) {
                echo '<span class="value error">❌ No hay API key guardada para ' . $provider_id . '_api_key</span>';
            } else {
                $api_key = $encrypted_key;
                if (!empty($encrypted_key) && $encryption && function_exists('flavor_decrypt_api_key')) {
                    $api_key = flavor_decrypt_api_key($encrypted_key);
                }

                if (empty($api_key)) {
                    echo '<span class="value warning">⚠️ Key encontrada pero no se pudo desencriptar</span>';
                } else {
                    $is_configured = !empty($api_key);
                    echo '<span class="value success">✅ is_configured() = ' . ($is_configured ? 'true' : 'false') . ' | Key length: ' . strlen($api_key) . '</span>';
                }
            }
            echo '</div>';
        }

        // Verificar el active_provider
        $active = $settings['active_provider'] ?? 'claude';
        echo '<div class="item">';
        echo '<span class="label">Provider activo (active_provider):</span>';
        echo '<span class="value"><code>' . $active . '</code>';
        if (empty($settings[$active . '_api_key']) && $active !== 'claude') {
            echo ' ⚠️ Sin API key configurada!';
        }
        echo '</span></div>';
        ?>
    </div>

    <div class="section">
        <h2>📝 Configuración Raw (Debug)</h2>
        <pre><?php
        $debug_settings = $settings;
        // Ocultar API keys completas en el debug
        foreach (['api_key', 'claude_api_key', 'openai_api_key', 'deepseek_api_key', 'mistral_api_key'] as $key) {
            if (!empty($debug_settings[$key])) {
                $debug_settings[$key] = '[HIDDEN - ' . strlen($debug_settings[$key]) . ' chars]';
            }
        }
        echo esc_html(print_r($debug_settings, true));
        ?></pre>
    </div>

    <script>
    async function testProvider(provider) {
        const resultDiv = document.getElementById('test-result');
        resultDiv.innerHTML = '<div class="test-result">⏳ Probando conexión con ' + provider + '...</div>';

        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'flavor_test_ia_connection',
                    provider: provider,
                    nonce: '<?php echo wp_create_nonce('flavor_chat_admin_nonce'); ?>'
                })
            });

            const data = await response.json();

            if (data.success) {
                resultDiv.innerHTML = '<div class="test-result success">✅ ' + data.data.message + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result error">❌ ' + (data.data?.message || data.data || 'Error desconocido') + '</div>';
            }
        } catch (error) {
            resultDiv.innerHTML = '<div class="test-result error">❌ Error: ' + error.message + '</div>';
        }
    }

    async function testSession() {
        const resultDiv = document.getElementById('session-result');
        resultDiv.innerHTML = '<div class="test-result">⏳ Probando crear sesión...</div>';

        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'chat_ia_init_session',
                    nonce: '<?php echo wp_create_nonce('flavor_chat_nonce'); ?>',
                    language: 'es'
                })
            });

            const data = await response.json();

            if (data.success) {
                resultDiv.innerHTML = '<div class="test-result success">✅ Sesión creada: ' + JSON.stringify(data.data) + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="test-result error">❌ Error: ' + (data.data?.message || data.data || JSON.stringify(data)) + '</div>';
            }
        } catch (error) {
            resultDiv.innerHTML = '<div class="test-result error">❌ Error: ' + error.message + '</div>';
        }
    }
    </script>
</body>
</html>
