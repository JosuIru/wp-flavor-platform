<?php
/**
 * Panel de privacidad del usuario
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_actual = wp_get_current_user();
?>

<div class="flavor-privacy-panel">
    <div class="flavor-privacy-header">
        <h2>Mi Privacidad y Datos Personales</h2>
        <p class="flavor-privacy-intro">
            Desde aquí puedes gestionar tus datos personales, consentimientos y solicitar la exportación o eliminación de tu información.
            En cumplimiento del Reglamento General de Protección de Datos (RGPD).
        </p>
    </div>

    <!-- Tabs de navegación -->
    <div class="flavor-privacy-tabs">
        <button class="flavor-tab-btn active" data-tab="resumen">
            <span class="dashicons dashicons-chart-bar"></span>
            Resumen
        </button>
        <button class="flavor-tab-btn" data-tab="consentimientos">
            <span class="dashicons dashicons-yes-alt"></span>
            Consentimientos
        </button>
        <button class="flavor-tab-btn" data-tab="datos">
            <span class="dashicons dashicons-database"></span>
            Mis Datos
        </button>
        <button class="flavor-tab-btn" data-tab="solicitudes">
            <span class="dashicons dashicons-clipboard"></span>
            Solicitudes
        </button>
    </div>

    <!-- Tab: Resumen -->
    <div class="flavor-tab-content active" id="tab-resumen">
        <div class="flavor-privacy-card">
            <h3>Información de tu cuenta</h3>
            <div class="flavor-info-grid">
                <div class="flavor-info-item">
                    <span class="label">Nombre</span>
                    <span class="value"><?php echo esc_html($usuario_actual->display_name); ?></span>
                </div>
                <div class="flavor-info-item">
                    <span class="label">Email</span>
                    <span class="value"><?php echo esc_html($usuario_actual->user_email); ?></span>
                </div>
                <div class="flavor-info-item">
                    <span class="label">Usuario</span>
                    <span class="value"><?php echo esc_html($usuario_actual->user_login); ?></span>
                </div>
                <div class="flavor-info-item">
                    <span class="label">Miembro desde</span>
                    <span class="value"><?php echo esc_html(date_i18n('d/m/Y', strtotime($usuario_actual->user_registered))); ?></span>
                </div>
            </div>
        </div>

        <div class="flavor-privacy-card">
            <h3>Resumen de tus datos</h3>
            <p class="flavor-card-description">Datos almacenados en la plataforma asociados a tu cuenta:</p>
            <div class="flavor-data-summary" id="data-summary">
                <div class="flavor-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    Cargando resumen...
                </div>
            </div>
        </div>

        <div class="flavor-privacy-card flavor-card-actions">
            <h3>Acciones rápidas</h3>
            <div class="flavor-action-buttons">
                <button type="button" class="flavor-btn flavor-btn-primary" id="btn-export-data">
                    <span class="dashicons dashicons-download"></span>
                    Descargar mis datos
                </button>
                <button type="button" class="flavor-btn flavor-btn-danger" id="btn-delete-account">
                    <span class="dashicons dashicons-trash"></span>
                    Solicitar eliminación
                </button>
            </div>
            <p class="flavor-action-note">
                <small>
                    La exportación generará un archivo ZIP con todos tus datos.
                    La eliminación debe ser aprobada por un administrador.
                </small>
            </p>
        </div>
    </div>

    <!-- Tab: Consentimientos -->
    <div class="flavor-tab-content" id="tab-consentimientos">
        <div class="flavor-privacy-card">
            <h3>Gestiona tus consentimientos</h3>
            <p class="flavor-card-description">
                Aquí puedes ver y modificar los permisos que nos has otorgado para el tratamiento de tus datos.
                Los consentimientos obligatorios son necesarios para el funcionamiento básico del servicio.
            </p>

            <div class="flavor-consents-list">
                <?php foreach ($consentimientos as $tipo => $info): ?>
                    <div class="flavor-consent-item <?php echo $info['obligatorio'] ? 'obligatorio' : ''; ?>">
                        <div class="flavor-consent-info">
                            <h4>
                                <?php echo esc_html($info['label']); ?>
                                <?php if ($info['obligatorio']): ?>
                                    <span class="flavor-badge obligatorio">Obligatorio</span>
                                <?php endif; ?>
                            </h4>
                            <p><?php echo esc_html($info['descripcion']); ?></p>
                            <?php if ($info['fecha']): ?>
                                <span class="flavor-consent-date">
                                    Última actualización: <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($info['fecha']))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-consent-toggle">
                            <label class="flavor-switch">
                                <input type="checkbox"
                                       name="consent_<?php echo esc_attr($tipo); ?>"
                                       data-tipo="<?php echo esc_attr($tipo); ?>"
                                       <?php checked($info['consentido']); ?>
                                       <?php disabled($info['obligatorio'] && $info['consentido']); ?>>
                                <span class="flavor-slider"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flavor-consents-footer">
                <button type="button" class="flavor-btn flavor-btn-secondary" id="btn-save-consents">
                    <span class="dashicons dashicons-saved"></span>
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>

    <!-- Tab: Mis Datos -->
    <div class="flavor-tab-content" id="tab-datos">
        <div class="flavor-privacy-card">
            <h3>Detalles de tus datos</h3>
            <p class="flavor-card-description">
                Vista detallada de la información que tenemos almacenada sobre ti, organizada por categorías.
            </p>

            <div class="flavor-data-categories" id="data-categories">
                <div class="flavor-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    Cargando datos...
                </div>
            </div>
        </div>

        <div class="flavor-privacy-card">
            <h3>Exportar datos</h3>
            <p class="flavor-card-description">
                Puedes descargar una copia de todos tus datos en formato ZIP.
                Este archivo incluirá tu perfil, publicaciones, mensajes y toda la información asociada a tu cuenta.
            </p>
            <button type="button" class="flavor-btn flavor-btn-primary" id="btn-request-export">
                <span class="dashicons dashicons-download"></span>
                Solicitar exportación
            </button>
            <p class="flavor-note">
                <span class="dashicons dashicons-info"></span>
                Recibirás un email con el enlace de descarga cuando la exportación esté lista (máximo 48 horas).
                Puedes solicitar hasta 2 exportaciones por semana.
            </p>
        </div>

        <div class="flavor-privacy-card flavor-card-danger">
            <h3>Eliminar cuenta</h3>
            <p class="flavor-card-description">
                Puedes solicitar la eliminación completa de tu cuenta y todos tus datos personales.
                Esta acción es <strong>irreversible</strong>.
            </p>

            <div class="flavor-delete-form" id="delete-form" style="display: none;">
                <div class="flavor-form-group">
                    <label for="delete-reason">Motivo (opcional):</label>
                    <textarea id="delete-reason" rows="3" placeholder="Cuéntanos por qué quieres eliminar tu cuenta..."></textarea>
                </div>
                <div class="flavor-form-group">
                    <label class="flavor-checkbox">
                        <input type="checkbox" id="confirm-delete">
                        Entiendo que esta acción eliminará permanentemente todos mis datos y no se puede deshacer.
                    </label>
                </div>
                <div class="flavor-form-actions">
                    <button type="button" class="flavor-btn flavor-btn-secondary" id="btn-cancel-delete">Cancelar</button>
                    <button type="button" class="flavor-btn flavor-btn-danger" id="btn-confirm-delete" disabled>
                        Confirmar eliminación
                    </button>
                </div>
            </div>

            <button type="button" class="flavor-btn flavor-btn-outline-danger" id="btn-show-delete-form">
                <span class="dashicons dashicons-warning"></span>
                Solicitar eliminación de cuenta
            </button>
        </div>
    </div>

    <!-- Tab: Solicitudes -->
    <div class="flavor-tab-content" id="tab-solicitudes">
        <div class="flavor-privacy-card">
            <h3>Historial de solicitudes</h3>
            <p class="flavor-card-description">
                Aquí puedes ver el estado de tus solicitudes de privacidad.
            </p>

            <div class="flavor-requests-list">
                <?php if (empty($solicitudes)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-clipboard"></span>
                        <p>No tienes solicitudes de privacidad.</p>
                    </div>
                <?php else: ?>
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Fecha solicitud</th>
                                <th>Fecha procesado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $solicitud): ?>
                                <?php
                                $datos = json_decode($solicitud['datos'] ?? '{}', true);
                                $estado_class = 'estado-' . $solicitud['estado'];
                                $estado_label = [
                                    'pendiente' => 'Pendiente',
                                    'procesando' => 'Procesando',
                                    'completado' => 'Completado',
                                    'rechazado' => 'Rechazado'
                                ][$solicitud['estado']] ?? $solicitud['estado'];
                                ?>
                                <tr>
                                    <td>
                                        <span class="flavor-request-type">
                                            <?php if ($solicitud['tipo'] === 'exportar'): ?>
                                                <span class="dashicons dashicons-download"></span>
                                                Exportación
                                            <?php else: ?>
                                                <span class="dashicons dashicons-trash"></span>
                                                Eliminación
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="flavor-status-badge <?php echo esc_attr($estado_class); ?>">
                                            <?php echo esc_html($estado_label); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($solicitud['fecha_solicitud']))); ?></td>
                                    <td>
                                        <?php if ($solicitud['fecha_procesado']): ?>
                                            <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($solicitud['fecha_procesado']))); ?>
                                        <?php else: ?>
                                            <span class="flavor-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($solicitud['tipo'] === 'exportar' && $solicitud['estado'] === 'completado' && isset($datos['token_hash'])): ?>
                                            <?php
                                            $expira = isset($datos['expira']) ? strtotime($datos['expira']) : 0;
                                            if ($expira > time()):
                                            ?>
                                                <span class="flavor-text-muted">Enlace enviado por email</span>
                                            <?php else: ?>
                                                <span class="flavor-text-muted">Enlace expirado</span>
                                            <?php endif; ?>
                                        <?php elseif ($solicitud['estado'] === 'rechazado' && isset($solicitud['motivo_rechazo'])): ?>
                                            <span class="flavor-text-muted" title="<?php echo esc_attr($solicitud['motivo_rechazo']); ?>">
                                                Ver motivo
                                            </span>
                                        <?php else: ?>
                                            <span class="flavor-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="flavor-privacy-card">
            <h3>Tus derechos RGPD</h3>
            <div class="flavor-rights-list">
                <div class="flavor-right-item">
                    <span class="dashicons dashicons-visibility"></span>
                    <div>
                        <h4>Derecho de acceso</h4>
                        <p>Puedes acceder a todos tus datos personales en cualquier momento desde esta página.</p>
                    </div>
                </div>
                <div class="flavor-right-item">
                    <span class="dashicons dashicons-edit"></span>
                    <div>
                        <h4>Derecho de rectificación</h4>
                        <p>Puedes modificar tus datos desde tu perfil de usuario.</p>
                    </div>
                </div>
                <div class="flavor-right-item">
                    <span class="dashicons dashicons-trash"></span>
                    <div>
                        <h4>Derecho de supresión</h4>
                        <p>Puedes solicitar la eliminación de todos tus datos personales.</p>
                    </div>
                </div>
                <div class="flavor-right-item">
                    <span class="dashicons dashicons-download"></span>
                    <div>
                        <h4>Derecho de portabilidad</h4>
                        <p>Puedes exportar todos tus datos en un formato legible por máquina.</p>
                    </div>
                </div>
                <div class="flavor-right-item">
                    <span class="dashicons dashicons-no"></span>
                    <div>
                        <h4>Derecho de oposición</h4>
                        <p>Puedes retirar tu consentimiento para usos no esenciales de tus datos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="flavor-modal" id="confirm-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h3 id="modal-title">Confirmar acción</h3>
            <button type="button" class="flavor-modal-close">&times;</button>
        </div>
        <div class="flavor-modal-body">
            <p id="modal-message"></p>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="flavor-btn flavor-btn-secondary" id="modal-cancel">Cancelar</button>
            <button type="button" class="flavor-btn flavor-btn-primary" id="modal-confirm">Confirmar</button>
        </div>
    </div>
</div>
