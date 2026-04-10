<?php
/**
 * Vista de detalle de un bug
 *
 * @package Flavor_Platform
 * @subpackage Bug_Tracker
 * @var object $bug_detalle Bug a mostrar
 */

if (!defined('ABSPATH')) {
    exit;
}

$colores_severidad = [
    'critical' => '#dc2626',
    'high' => '#ea580c',
    'medium' => '#ca8a04',
    'low' => '#2563eb',
    'info' => '#6b7280',
];

$colores_estado = [
    'nuevo' => '#dc2626',
    'abierto' => '#ea580c',
    'resuelto' => '#16a34a',
    'ignorado' => '#6b7280',
];

$emojis_tipo = [
    'error_php' => '💥',
    'exception' => '⚠️',
    'warning' => '⚡',
    'notice' => '📝',
    'manual' => '📋',
    'crash' => '💀',
    'deprecation' => '🕰️',
];
?>

<style>
.bug-detail-container {
    max-width: 1000px;
}
.bug-detail-header {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.bug-detail-header h2 {
    margin: 0 0 15px 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.bug-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}
.bug-detail-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
}
.bug-detail-meta .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    color: white;
}
.bug-detail-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}
.bug-detail-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.bug-detail-section h3 {
    margin: 0 0 15px 0;
    font-size: 14px;
    text-transform: uppercase;
    color: #666;
    letter-spacing: 0.5px;
}
.bug-detail-section pre {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 12px;
    line-height: 1.5;
    margin: 0;
}
.bug-detail-section .message-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 15px;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 13px;
    white-space: pre-wrap;
    word-break: break-word;
}
.context-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}
.context-item {
    background: #f9fafb;
    padding: 10px;
    border-radius: 4px;
}
.context-item .label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 3px;
}
.context-item .value {
    font-size: 13px;
    word-break: break-all;
}
.notes-section textarea {
    width: 100%;
    min-height: 100px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 13px;
    resize: vertical;
}
.notes-history {
    background: #f9fafb;
    border-radius: 6px;
    padding: 15px;
    margin-top: 15px;
    white-space: pre-wrap;
    font-size: 13px;
    max-height: 200px;
    overflow-y: auto;
}
</style>

<div class="bug-detail-container">
    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bug-tracker')); ?>" class="button">
            ← <?php esc_html_e('Volver a la lista', 'flavor-platform'); ?>
        </a>
    </p>

    <!-- Header -->
    <div class="bug-detail-header">
        <h2>
            <span style="font-size: 24px;"><?php echo esc_html($emojis_tipo[$bug_detalle->tipo] ?? '🐛'); ?></span>
            <?php echo esc_html($bug_detalle->titulo); ?>
        </h2>

        <div class="bug-detail-meta">
            <div class="meta-item">
                <strong><?php esc_html_e('Código:', 'flavor-platform'); ?></strong>
                <code><?php echo esc_html($bug_detalle->codigo); ?></code>
            </div>

            <div class="meta-item">
                <span class="badge" style="background: <?php echo esc_attr($colores_severidad[$bug_detalle->severidad] ?? '#6b7280'); ?>;">
                    <?php echo esc_html(ucfirst($bug_detalle->severidad)); ?>
                </span>
            </div>

            <div class="meta-item">
                <span class="badge" style="background: <?php echo esc_attr($colores_estado[$bug_detalle->estado] ?? '#6b7280'); ?>;">
                    <?php echo esc_html(ucfirst($bug_detalle->estado)); ?>
                </span>
            </div>

            <div class="meta-item">
                <strong><?php esc_html_e('Tipo:', 'flavor-platform'); ?></strong>
                <?php echo esc_html(str_replace('_', ' ', ucfirst($bug_detalle->tipo))); ?>
            </div>

            <div class="meta-item">
                <strong><?php esc_html_e('Ocurrencias:', 'flavor-platform'); ?></strong>
                <?php echo esc_html($bug_detalle->ocurrencias); ?>
            </div>
        </div>

        <div class="bug-detail-actions">
            <?php if ($bug_detalle->estado !== 'resuelto') : ?>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('bug_action_' . $bug_detalle->id); ?>
                    <input type="hidden" name="bug_id" value="<?php echo esc_attr($bug_detalle->id); ?>">
                    <input type="hidden" name="accion" value="resolver">
                    <button type="submit" class="button button-primary">
                        ✓ <?php esc_html_e('Marcar como Resuelto', 'flavor-platform'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($bug_detalle->estado !== 'ignorado') : ?>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('bug_action_' . $bug_detalle->id); ?>
                    <input type="hidden" name="bug_id" value="<?php echo esc_attr($bug_detalle->id); ?>">
                    <input type="hidden" name="accion" value="ignorar">
                    <button type="submit" class="button">
                        ✗ <?php esc_html_e('Ignorar', 'flavor-platform'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($bug_detalle->estado === 'resuelto' || $bug_detalle->estado === 'ignorado') : ?>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('bug_action_' . $bug_detalle->id); ?>
                    <input type="hidden" name="bug_id" value="<?php echo esc_attr($bug_detalle->id); ?>">
                    <input type="hidden" name="accion" value="reabrir">
                    <button type="submit" class="button">
                        ↺ <?php esc_html_e('Reabrir', 'flavor-platform'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ubicación -->
    <?php if ($bug_detalle->archivo) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Ubicación', 'flavor-platform'); ?></h3>
            <div class="context-grid">
                <div class="context-item">
                    <div class="label"><?php esc_html_e('Archivo', 'flavor-platform'); ?></div>
                    <div class="value"><code><?php echo esc_html($bug_detalle->archivo); ?></code></div>
                </div>
                <div class="context-item">
                    <div class="label"><?php esc_html_e('Línea', 'flavor-platform'); ?></div>
                    <div class="value"><?php echo esc_html($bug_detalle->linea); ?></div>
                </div>
                <?php if ($bug_detalle->modulo_id) : ?>
                    <div class="context-item">
                        <div class="label"><?php esc_html_e('Módulo', 'flavor-platform'); ?></div>
                        <div class="value"><?php echo esc_html($bug_detalle->modulo_id); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mensaje -->
    <?php if ($bug_detalle->mensaje) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Mensaje de Error', 'flavor-platform'); ?></h3>
            <div class="message-box"><?php echo esc_html($bug_detalle->mensaje); ?></div>
        </div>
    <?php endif; ?>

    <!-- Stack Trace -->
    <?php if ($bug_detalle->stack_trace) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Stack Trace', 'flavor-platform'); ?></h3>
            <pre><?php echo esc_html($bug_detalle->stack_trace); ?></pre>
        </div>
    <?php endif; ?>

    <!-- Contexto de Request -->
    <?php if (!empty($bug_detalle->contexto_request)) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Contexto de Request', 'flavor-platform'); ?></h3>
            <div class="context-grid">
                <?php foreach ($bug_detalle->contexto_request as $clave => $valor) : ?>
                    <div class="context-item">
                        <div class="label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $clave))); ?></div>
                        <div class="value">
                            <?php
                            if (is_bool($valor)) {
                                echo $valor ? 'Sí' : 'No';
                            } elseif (is_array($valor)) {
                                echo esc_html(implode(', ', $valor));
                            } else {
                                echo esc_html($valor ?: '-');
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contexto de Servidor -->
    <?php if (!empty($bug_detalle->contexto_servidor)) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Contexto del Servidor', 'flavor-platform'); ?></h3>
            <div class="context-grid">
                <?php foreach ($bug_detalle->contexto_servidor as $clave => $valor) : ?>
                    <div class="context-item">
                        <div class="label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $clave))); ?></div>
                        <div class="value"><?php echo esc_html($valor ?: '-'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contexto de Usuario -->
    <?php if (!empty($bug_detalle->contexto_usuario)) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Usuario Afectado', 'flavor-platform'); ?></h3>
            <div class="context-grid">
                <?php foreach ($bug_detalle->contexto_usuario as $clave => $valor) : ?>
                    <div class="context-item">
                        <div class="label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $clave))); ?></div>
                        <div class="value">
                            <?php
                            if (is_array($valor)) {
                                echo esc_html(implode(', ', $valor));
                            } else {
                                echo esc_html($valor ?: '-');
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Timestamps -->
    <div class="bug-detail-section">
        <h3><?php esc_html_e('Historial de Tiempo', 'flavor-platform'); ?></h3>
        <div class="context-grid">
            <div class="context-item">
                <div class="label"><?php esc_html_e('Primera Ocurrencia', 'flavor-platform'); ?></div>
                <div class="value"><?php echo esc_html($bug_detalle->primera_ocurrencia); ?></div>
            </div>
            <div class="context-item">
                <div class="label"><?php esc_html_e('Última Ocurrencia', 'flavor-platform'); ?></div>
                <div class="value"><?php echo esc_html($bug_detalle->ultima_ocurrencia); ?></div>
            </div>
            <?php if ($bug_detalle->resuelto_at) : ?>
                <div class="context-item">
                    <div class="label"><?php esc_html_e('Resuelto el', 'flavor-platform'); ?></div>
                    <div class="value"><?php echo esc_html($bug_detalle->resuelto_at); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($bug_detalle->resuelto_por) : ?>
                <div class="context-item">
                    <div class="label"><?php esc_html_e('Resuelto por', 'flavor-platform'); ?></div>
                    <div class="value">
                        <?php
                        $usuario_resolvio = get_user_by('ID', $bug_detalle->resuelto_por);
                        echo $usuario_resolvio ? esc_html($usuario_resolvio->display_name) : esc_html__('Usuario desconocido', 'flavor-platform');
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notas -->
    <div class="bug-detail-section notes-section">
        <h3><?php esc_html_e('Notas', 'flavor-platform'); ?></h3>

        <form method="post">
            <?php wp_nonce_field('bug_action_' . $bug_detalle->id); ?>
            <input type="hidden" name="bug_id" value="<?php echo esc_attr($bug_detalle->id); ?>">
            <input type="hidden" name="accion" value="agregar_nota">

            <textarea name="nota" placeholder="<?php esc_attr_e('Agregar una nota...', 'flavor-platform'); ?>"></textarea>

            <p style="margin-top: 10px;">
                <button type="submit" class="button"><?php esc_html_e('Agregar Nota', 'flavor-platform'); ?></button>
            </p>
        </form>

        <?php if ($bug_detalle->notas) : ?>
            <div class="notes-history">
                <?php echo esc_html($bug_detalle->notas); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Debug Info -->
    <?php if (!empty($bug_detalle->contexto_extra)) : ?>
        <div class="bug-detail-section">
            <h3><?php esc_html_e('Información Adicional', 'flavor-platform'); ?></h3>
            <pre><?php echo esc_html(wp_json_encode($bug_detalle->contexto_extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    <?php endif; ?>

    <!-- Fingerprint (para debug) -->
    <div class="bug-detail-section">
        <h3><?php esc_html_e('Información Técnica', 'flavor-platform'); ?></h3>
        <div class="context-grid">
            <div class="context-item">
                <div class="label"><?php esc_html_e('ID', 'flavor-platform'); ?></div>
                <div class="value"><?php echo esc_html($bug_detalle->id); ?></div>
            </div>
            <div class="context-item">
                <div class="label"><?php esc_html_e('Hash Fingerprint', 'flavor-platform'); ?></div>
                <div class="value"><code style="font-size: 10px;"><?php echo esc_html($bug_detalle->hash_fingerprint); ?></code></div>
            </div>
        </div>
    </div>
</div>

<?php
// Procesar acciones del formulario
if (isset($_POST['accion']) && isset($_POST['bug_id'])) {
    $nonce_action = 'bug_action_' . intval($_POST['bug_id']);
    if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), $nonce_action)) {
        $bug_id = intval($_POST['bug_id']);
        $accion = sanitize_text_field($_POST['accion']);

        switch ($accion) {
            case 'resolver':
                $this->actualizar_estado_bug($bug_id, 'resuelto');
                wp_safe_redirect(add_query_arg(['page' => 'flavor-bug-tracker', 'bug_id' => $bug_id], admin_url('admin.php')));
                exit;

            case 'ignorar':
                $this->actualizar_estado_bug($bug_id, 'ignorado');
                wp_safe_redirect(add_query_arg(['page' => 'flavor-bug-tracker', 'bug_id' => $bug_id], admin_url('admin.php')));
                exit;

            case 'reabrir':
                $this->actualizar_estado_bug($bug_id, 'abierto');
                wp_safe_redirect(add_query_arg(['page' => 'flavor-bug-tracker', 'bug_id' => $bug_id], admin_url('admin.php')));
                exit;

            case 'agregar_nota':
                $nota = sanitize_textarea_field($_POST['nota'] ?? '');
                if (!empty($nota)) {
                    global $wpdb;
                    $tabla = $this->get_tabla_bugs();
                    $bug_actual = $this->obtener_bug($bug_id);
                    $notas_anteriores = $bug_actual ? $bug_actual->notas : '';
                    $nueva_nota = sprintf(
                        "[%s - %s]\n%s",
                        current_time('Y-m-d H:i'),
                        wp_get_current_user()->display_name,
                        $nota
                    );
                    $notas_actualizadas = $notas_anteriores ? $notas_anteriores . "\n\n" . $nueva_nota : $nueva_nota;
                    $wpdb->update($tabla, ['notas' => $notas_actualizadas], ['id' => $bug_id]);
                }
                wp_safe_redirect(add_query_arg(['page' => 'flavor-bug-tracker', 'bug_id' => $bug_id], admin_url('admin.php')));
                exit;
        }
    }
}
