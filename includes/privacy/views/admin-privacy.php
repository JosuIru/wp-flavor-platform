<?php
/**
 * Panel de administración de privacidad
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-admin-privacy">
    <h1>
        <span class="dashicons dashicons-shield"></span>
        Privacidad y RGPD
    </h1>

    <!-- Estadísticas -->
    <div class="flavor-admin-stats">
        <div class="flavor-stat-card">
            <span class="dashicons dashicons-clipboard"></span>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo esc_html($estadisticas['total_solicitudes']); ?></span>
                <span class="flavor-stat-label">Total solicitudes</span>
            </div>
        </div>
        <div class="flavor-stat-card warning">
            <span class="dashicons dashicons-warning"></span>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo esc_html($estadisticas['pendientes']); ?></span>
                <span class="flavor-stat-label">Pendientes</span>
            </div>
        </div>
        <div class="flavor-stat-card success">
            <span class="dashicons dashicons-yes-alt"></span>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo esc_html($estadisticas['completadas']); ?></span>
                <span class="flavor-stat-label">Completadas</span>
            </div>
        </div>
        <div class="flavor-stat-card info">
            <span class="dashicons dashicons-download"></span>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo esc_html($estadisticas['exportaciones']); ?></span>
                <span class="flavor-stat-label">Exportaciones</span>
            </div>
        </div>
        <div class="flavor-stat-card danger">
            <span class="dashicons dashicons-trash"></span>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo esc_html($estadisticas['eliminaciones']); ?></span>
                <span class="flavor-stat-label">Eliminaciones</span>
            </div>
        </div>
    </div>

    <!-- Solicitudes pendientes -->
    <?php if (!empty($solicitudes_pendientes)): ?>
        <div class="flavor-admin-section">
            <h2>
                <span class="dashicons dashicons-warning"></span>
                Solicitudes pendientes de revisión
            </h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Fecha solicitud</th>
                        <th>Datos</th>
                        <th style="width: 200px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes_pendientes as $solicitud): ?>
                        <?php $datos = json_decode($solicitud->datos ?? '{}', true); ?>
                        <tr>
                            <td><?php echo esc_html($solicitud->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($solicitud->display_name); ?></strong>
                                <br>
                                <small>ID: <?php echo esc_html($solicitud->usuario_id); ?></small>
                            </td>
                            <td><?php echo esc_html($solicitud->user_email); ?></td>
                            <td>
                                <?php if ($solicitud->tipo === 'exportar'): ?>
                                    <span class="dashicons dashicons-download"></span> Exportación
                                <?php else: ?>
                                    <span class="dashicons dashicons-trash" style="color: #dc3545;"></span>
                                    <strong style="color: #dc3545;">Eliminación</strong>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($solicitud->fecha_solicitud))); ?></td>
                            <td>
                                <?php if (!empty($datos['motivo'])): ?>
                                    <em>"<?php echo esc_html(wp_trim_words($datos['motivo'], 10)); ?>"</em>
                                <?php else: ?>
                                    <span class="description">Sin motivo especificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($solicitud->tipo === 'eliminar'): ?>
                                    <form method="post" style="display: inline-flex; gap: 5px; flex-wrap: wrap;">
                                        <?php wp_nonce_field('flavor_privacy_admin'); ?>
                                        <input type="hidden" name="action" value="process_request">
                                        <input type="hidden" name="solicitud_id" value="<?php echo esc_attr($solicitud->id); ?>">

                                        <button type="submit" name="decision" value="aprobar" class="button button-primary"
                                                onclick="return confirm('¿Confirmas la ELIMINACIÓN de todos los datos de este usuario? Esta acción es IRREVERSIBLE.');">
                                            Aprobar
                                        </button>

                                        <button type="button" class="button"
                                                onclick="this.nextElementSibling.style.display='block'; this.style.display='none';">
                                            Rechazar
                                        </button>

                                        <div style="display: none; margin-top: 5px; width: 100%;">
                                            <input type="text" name="motivo_rechazo" placeholder="Motivo del rechazo" style="width: 100%;">
                                            <button type="submit" name="decision" value="rechazar" class="button" style="margin-top: 5px;">
                                                Confirmar rechazo
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="description">Las exportaciones se procesan automáticamente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="flavor-admin-notice success">
            <span class="dashicons dashicons-yes-alt"></span>
            No hay solicitudes pendientes de revisión.
        </div>
    <?php endif; ?>

    <!-- Información del sistema -->
    <div class="flavor-admin-section">
        <h2>
            <span class="dashicons dashicons-info"></span>
            Información del sistema RGPD
        </h2>

        <div class="flavor-admin-grid">
            <div class="flavor-admin-card">
                <h3>Shortcodes disponibles</h3>
                <table class="widefat">
                    <tr>
                        <td><code>[flavor_privacidad]</code></td>
                        <td>Panel completo de privacidad del usuario</td>
                    </tr>
                    <tr>
                        <td><code>[flavor_consentimientos]</code></td>
                        <td>Formulario de consentimientos</td>
                    </tr>
                </table>
            </div>

            <div class="flavor-admin-card">
                <h3>Endpoints REST API</h3>
                <table class="widefat">
                    <tr>
                        <td><code>GET /flavor-app/v1/privacidad/mis-datos</code></td>
                        <td>Obtener datos del usuario</td>
                    </tr>
                    <tr>
                        <td><code>POST /flavor-app/v1/privacidad/exportar</code></td>
                        <td>Solicitar exportación</td>
                    </tr>
                    <tr>
                        <td><code>POST /flavor-app/v1/privacidad/eliminar-cuenta</code></td>
                        <td>Solicitar eliminación</td>
                    </tr>
                    <tr>
                        <td><code>GET /flavor-app/v1/privacidad/consentimientos</code></td>
                        <td>Ver consentimientos</td>
                    </tr>
                    <tr>
                        <td><code>POST /flavor-app/v1/privacidad/consentimientos</code></td>
                        <td>Actualizar consentimientos</td>
                    </tr>
                </table>
            </div>

            <div class="flavor-admin-card">
                <h3>Tipos de consentimiento</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Obligatorio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (Flavor_Privacy_Manager::CONSENT_TYPES as $tipo => $info): ?>
                            <tr>
                                <td><?php echo esc_html($info['label']); ?></td>
                                <td>
                                    <?php if ($info['obligatorio']): ?>
                                        <span class="dashicons dashicons-yes" style="color: #dc3545;"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-no-alt" style="color: #999;"></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flavor-admin-card">
                <h3>Configuración de retención</h3>
                <ul>
                    <li><strong>Archivos de exportación:</strong> 48 horas</li>
                    <li><strong>Límite de exportaciones:</strong> 2 por semana</li>
                    <li><strong>Procesamiento automático:</strong> Cada hora (cron)</li>
                    <li><strong>Limpieza de archivos:</strong> Diaria (cron)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Acciones manuales -->
    <div class="flavor-admin-section">
        <h2>
            <span class="dashicons dashicons-admin-tools"></span>
            Acciones de mantenimiento
        </h2>

        <div class="flavor-admin-actions">
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('flavor_privacy_admin'); ?>
                <input type="hidden" name="action" value="cleanup_exports">
                <button type="submit" class="button">
                    <span class="dashicons dashicons-trash"></span>
                    Limpiar exportaciones antiguas
                </button>
            </form>

            <form method="post" style="display: inline;">
                <?php wp_nonce_field('flavor_privacy_admin'); ?>
                <input type="hidden" name="action" value="process_pending">
                <button type="submit" class="button">
                    <span class="dashicons dashicons-update"></span>
                    Procesar exportaciones pendientes
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.flavor-admin-privacy h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-admin-stats {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.flavor-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 150px;
}

.flavor-stat-card .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    color: #2271b1;
}

.flavor-stat-card.warning .dashicons { color: #dba617; }
.flavor-stat-card.success .dashicons { color: #00a32a; }
.flavor-stat-card.danger .dashicons { color: #dc3545; }
.flavor-stat-card.info .dashicons { color: #2271b1; }

.flavor-stat-value {
    font-size: 24px;
    font-weight: 600;
    display: block;
}

.flavor-stat-label {
    color: #646970;
    font-size: 12px;
}

.flavor-admin-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.flavor-admin-section h2 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-admin-notice {
    padding: 15px 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
}

.flavor-admin-notice.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.flavor-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.flavor-admin-card {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
}

.flavor-admin-card h3 {
    margin-top: 0;
    border-bottom: 1px solid #c3c4c7;
    padding-bottom: 10px;
}

.flavor-admin-card table {
    background: transparent;
}

.flavor-admin-card code {
    font-size: 11px;
}

.flavor-admin-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.flavor-admin-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
</style>
