<?php
/**
 * Configuración del Módulo Empresas - Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = $this->get_all_settings();
?>
<div class="wrap flavor-modulo-page">
    <?php $this->render_page_header(__('Configuración de Empresas', 'flavor-platform')); ?>

    <?php if (isset($_GET['updated'])): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e('Configuración guardada correctamente.', 'flavor-platform'); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('guardar_config_empresas'); ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <!-- Columna izquierda -->
            <div>
                <!-- Configuración general -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                    <h3 style="margin:0 0 20px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-admin-settings" style="color:#3b82f6;"></span>
                        <?php esc_html_e('Configuración general', 'flavor-platform'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th><label for="requiere_aprobacion"><?php esc_html_e('Registro de empresas', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="requiere_aprobacion" id="requiere_aprobacion" value="1"
                                           <?php checked($settings['requiere_aprobacion'] ?? true); ?> />
                                    <?php esc_html_e('Requiere aprobación de administrador', 'flavor-platform'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Si está activo, las nuevas empresas quedarán pendientes hasta ser aprobadas.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="max_miembros"><?php esc_html_e('Máximo de miembros', 'flavor-platform'); ?></label></th>
                            <td>
                                <input type="number" name="max_miembros" id="max_miembros" min="0" style="width:80px;"
                                       value="<?php echo esc_attr($settings['max_miembros'] ?? 0); ?>" />
                                <p class="description"><?php esc_html_e('0 = sin límite. Límite de miembros por empresa.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="permitir_multiples"><?php esc_html_e('Empresas por usuario', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="permitir_multiples" id="permitir_multiples" value="1"
                                           <?php checked($settings['permitir_multiples'] ?? false); ?> />
                                    <?php esc_html_e('Permitir que un usuario pertenezca a múltiples empresas', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Campos requeridos -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                    <h3 style="margin:0 0 20px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-forms" style="color:#10b981;"></span>
                        <?php esc_html_e('Campos requeridos', 'flavor-platform'); ?>
                    </h3>

                    <?php
                    $campos_disponibles = [
                        'cif_nif' => __('CIF/NIF', 'flavor-platform'),
                        'razon_social' => __('Razón social', 'flavor-platform'),
                        'email' => __('Email', 'flavor-platform'),
                        'telefono' => __('Teléfono', 'flavor-platform'),
                        'direccion' => __('Dirección', 'flavor-platform'),
                        'sector' => __('Sector', 'flavor-platform'),
                    ];
                    $campos_requeridos = $settings['campos_requeridos'] ?? ['cif_nif'];
                    ?>
                    <p style="color:#666;margin-bottom:16px;"><?php esc_html_e('Selecciona los campos obligatorios al registrar una empresa:', 'flavor-platform'); ?></p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <?php foreach ($campos_disponibles as $campo => $label): ?>
                        <label style="display:flex;align-items:center;gap:8px;padding:8px;background:#f8fafc;border-radius:6px;">
                            <input type="checkbox" name="campos_requeridos[]" value="<?php echo esc_attr($campo); ?>"
                                   <?php checked(in_array($campo, $campos_requeridos)); ?> />
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Roles y permisos -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 20px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-groups" style="color:#8b5cf6;"></span>
                        <?php esc_html_e('Roles disponibles', 'flavor-platform'); ?>
                    </h3>

                    <?php
                    $roles_disponibles = $settings['roles_disponibles'] ?? ['admin', 'contable', 'empleado', 'colaborador', 'observador'];
                    $roles_todos = [
                        'admin' => ['label' => __('Administrador', 'flavor-platform'), 'desc' => __('Control total de la empresa', 'flavor-platform')],
                        'contable' => ['label' => __('Contable', 'flavor-platform'), 'desc' => __('Acceso a contabilidad y facturas', 'flavor-platform')],
                        'empleado' => ['label' => __('Empleado', 'flavor-platform'), 'desc' => __('Acceso básico a funciones', 'flavor-platform')],
                        'colaborador' => ['label' => __('Colaborador', 'flavor-platform'), 'desc' => __('Acceso limitado, colaborador externo', 'flavor-platform')],
                        'observador' => ['label' => __('Observador', 'flavor-platform'), 'desc' => __('Solo lectura', 'flavor-platform')],
                    ];
                    ?>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($roles_todos as $rol => $info): ?>
                        <label style="display:flex;align-items:start;gap:10px;padding:12px;background:#f8fafc;border-radius:8px;<?php echo $rol === 'admin' ? 'opacity:0.6;' : ''; ?>">
                            <input type="checkbox" name="roles_disponibles[]" value="<?php echo esc_attr($rol); ?>"
                                   <?php checked(in_array($rol, $roles_disponibles)); ?>
                                   <?php disabled($rol, 'admin'); ?> />
                            <div>
                                <strong><?php echo esc_html($info['label']); ?></strong>
                                <br><small style="color:#666;"><?php echo esc_html($info['desc']); ?></small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description" style="margin-top:12px;"><?php esc_html_e('El rol Administrador siempre está disponible.', 'flavor-platform'); ?></p>
                </div>
            </div>

            <!-- Columna derecha -->
            <div>
                <!-- Sectores -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                    <h3 style="margin:0 0 20px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-category" style="color:#f59e0b;"></span>
                        <?php esc_html_e('Sectores de actividad', 'flavor-platform'); ?>
                    </h3>

                    <?php
                    $sectores = $settings['sectores'] ?? ['tecnología', 'comercio', 'servicios', 'industria', 'agricultura', 'construcción', 'hostelería', 'transporte', 'educación', 'salud', 'otros'];
                    ?>
                    <textarea name="sectores" rows="10" class="large-text" placeholder="<?php esc_attr_e('Un sector por línea...', 'flavor-platform'); ?>"><?php echo esc_textarea(implode("\n", $sectores)); ?></textarea>
                    <p class="description"><?php esc_html_e('Introduce un sector por línea. Estos aparecerán en el formulario de registro.', 'flavor-platform'); ?></p>
                </div>

                <!-- Integraciones -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-bottom:20px;">
                    <h3 style="margin:0 0 20px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-admin-plugins" style="color:#3b82f6;"></span>
                        <?php esc_html_e('Integraciones', 'flavor-platform'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th><label><?php esc_html_e('Contabilidad', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="integrar_contabilidad" value="1"
                                           <?php checked($settings['integrar_contabilidad'] ?? true); ?> />
                                    <?php esc_html_e('Habilitar contabilidad por empresa', 'flavor-platform'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Cada empresa tendrá su propia contabilidad separada.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Facturas', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="integrar_facturas" value="1"
                                           <?php checked($settings['integrar_facturas'] ?? true); ?> />
                                    <?php esc_html_e('Habilitar facturación por empresa', 'flavor-platform'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Cada empresa podrá emitir y recibir sus propias facturas.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Documentos', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="habilitar_documentos" value="1"
                                           <?php checked($settings['habilitar_documentos'] ?? true); ?> />
                                    <?php esc_html_e('Habilitar gestor documental', 'flavor-platform'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Permite subir y organizar documentos por empresa.', 'flavor-platform'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Notificaciones -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 20px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-email-alt" style="color:#ef4444;"></span>
                        <?php esc_html_e('Notificaciones', 'flavor-platform'); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th><label><?php esc_html_e('Nueva solicitud', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notificar_nueva_solicitud" value="1"
                                           <?php checked($settings['notificar_nueva_solicitud'] ?? true); ?> />
                                    <?php esc_html_e('Notificar a admins cuando hay nueva solicitud', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Solicitud aprobada', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notificar_aprobacion" value="1"
                                           <?php checked($settings['notificar_aprobacion'] ?? true); ?> />
                                    <?php esc_html_e('Notificar al solicitante cuando se aprueba', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e('Nuevo miembro', 'flavor-platform'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="notificar_nuevo_miembro" value="1"
                                           <?php checked($settings['notificar_nuevo_miembro'] ?? true); ?> />
                                    <?php esc_html_e('Notificar a admins de la empresa', 'flavor-platform'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-top:24px;">
            <?php submit_button(__('Guardar configuración', 'flavor-platform'), 'primary', 'guardar_config'); ?>
        </div>
    </form>
</div>
